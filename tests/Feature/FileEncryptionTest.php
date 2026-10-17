<?php

use App\Models\ScreeningRequest;
use App\Support\EncryptedFilesystemAdapter;
use App\Support\Pii;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Tests\Support\Fixtures;

beforeEach(function () {
    $this->root = storage_path('framework/testing/encrypted-'.uniqid());
    $this->clientRoot = $this->root.'/client';
    $this->adminRoot = $this->root.'/admin';
    File::ensureDirectoryExists($this->clientRoot);
    File::ensureDirectoryExists($this->adminRoot);

    // Point both private disks at throwaway roots; Storage::fake() would swap
    // in the plain local driver and bypass the encryption under test.
    config([
        'filesystems.disks.client_local.root' => $this->clientRoot,
        'filesystems.disks.local.root' => $this->adminRoot,
        'pii.key' => 'base64:'.base64_encode(random_bytes(32)),
    ]);
    Storage::forgetDisk(['client_local', 'local']);
    Pii::flush();
});

afterEach(function () {
    config(['pii.key' => '']);
    Pii::flush();
    Storage::forgetDisk(['client_local', 'local']);
    File::deleteDirectory($this->root);
});

it('stores ciphertext on disk and reads plaintext back', function () {
    $plain = '%PDF-1.4 IC 900101-01-1234 secret slip';
    Storage::disk('client_local')->put('payment-slips/1/slip.pdf', $plain);

    $raw = file_get_contents($this->clientRoot.'/payment-slips/1/slip.pdf');

    expect(EncryptedFilesystemAdapter::isEncrypted($raw))->toBeTrue()
        ->and($raw)->not->toContain('900101-01-1234')
        ->and(Storage::disk('client_local')->get('payment-slips/1/slip.pdf'))->toBe($plain)
        ->and(Storage::disk('client_local')->size('payment-slips/1/slip.pdf'))->toBe(strlen($plain))
        ->and(Storage::disk('client_local')->mimeType('payment-slips/1/slip.pdf'))->toBe('application/pdf');
});

it('still reads legacy plaintext files written before encryption', function () {
    File::ensureDirectoryExists($this->clientRoot.'/receipts');
    file_put_contents($this->clientRoot.'/receipts/old.pdf', '%PDF-legacy');

    expect(Storage::disk('client_local')->get('receipts/old.pdf'))->toBe('%PDF-legacy')
        ->and(Storage::disk('client_local')->size('receipts/old.pdf'))->toBe(11);
});

it('writes plaintext when no PII key is configured', function () {
    config(['pii.key' => '']);
    Pii::flush();

    Storage::disk('local')->put('dsar/evidence.pdf', '%PDF-plain');

    expect(file_get_contents($this->adminRoot.'/dsar/evidence.pdf'))->toBe('%PDF-plain');
});

it('refuses to return tampered ciphertext', function () {
    Storage::disk('local')->put('consent/1/form.pdf', '%PDF-consent');
    $full = $this->adminRoot.'/consent/1/form.pdf';
    $raw = file_get_contents($full);
    file_put_contents($full, substr($raw, 0, -1).chr(ord($raw[-1]) ^ 1));

    // Disks are configured with throw:false, so a failed read surfaces as null.
    expect(Storage::disk('local')->get('consent/1/form.pdf'))->toBeNull();
});

it('serves an encrypted payment slip decrypted to an authorised admin', function () {
    Fixtures::seedPermissions();
    $admin = Fixtures::admin(['role' => 'super_admin']);
    $customerId = Fixtures::customer();
    $userId = Fixtures::customerUser($customerId);

    $path = "payment-slips/{$customerId}/slip.pdf";
    Storage::disk('client_local')->put($path, '%PDF-1.4 real slip');
    $requestId = Fixtures::screeningRequest($customerId, $userId, [
        'payment_slip_path' => $path,
        'payment_slip_uploaded_at' => now(),
    ]);

    $response = Fixtures::actingAs($this, $admin)
        ->get(route('requests.payment-slip.download', ScreeningRequest::find($requestId)))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/pdf')
        ->assertHeader('Content-Length', (string) strlen('%PDF-1.4 real slip'));

    expect($response->streamedContent())->toBe('%PDF-1.4 real slip');
});

it('encrypts the plaintext backlog with pii:encrypt-files --apply', function () {
    File::ensureDirectoryExists($this->clientRoot.'/candidate-documents/1');
    File::ensureDirectoryExists($this->adminRoot.'/reports/1');
    file_put_contents($this->clientRoot.'/candidate-documents/1/nric.pdf', '%PDF-nric');
    file_put_contents($this->adminRoot.'/reports/1/full-v1.pdf', '%PDF-report');
    file_put_contents($this->clientRoot.'/.gitignore', "*\n");
    Storage::disk('client_local')->put('payment-slips/1/new.pdf', '%PDF-already-sealed');
    $sealedBefore = file_get_contents($this->clientRoot.'/payment-slips/1/new.pdf');

    // Dry run changes nothing.
    $this->artisan('pii:encrypt-files')->expectsOutputToContain('2 plaintext file(s) found')->assertSuccessful();
    expect(file_get_contents($this->clientRoot.'/candidate-documents/1/nric.pdf'))->toBe('%PDF-nric');

    $this->artisan('pii:encrypt-files --apply')->expectsOutputToContain('Encrypted 2 file(s)')->assertSuccessful();

    expect(EncryptedFilesystemAdapter::isEncrypted(file_get_contents($this->clientRoot.'/candidate-documents/1/nric.pdf')))->toBeTrue()
        ->and(EncryptedFilesystemAdapter::isEncrypted(file_get_contents($this->adminRoot.'/reports/1/full-v1.pdf')))->toBeTrue()
        ->and(Storage::disk('client_local')->get('candidate-documents/1/nric.pdf'))->toBe('%PDF-nric')
        ->and(Storage::disk('local')->get('reports/1/full-v1.pdf'))->toBe('%PDF-report')
        ->and(file_get_contents($this->clientRoot.'/.gitignore'))->toBe("*\n")
        ->and(file_get_contents($this->clientRoot.'/payment-slips/1/new.pdf'))->toBe($sealedBefore);

    // Idempotent.
    $this->artisan('pii:encrypt-files')->expectsOutputToContain('0 plaintext file(s) found')->assertSuccessful();
});

it('refuses to run without a PII key', function () {
    config(['pii.key' => '']);
    Pii::flush();

    $this->artisan('pii:encrypt-files --apply')->assertFailed();
});
