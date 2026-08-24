<?php

use App\Models\AdminAuditLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\Support\Fixtures;

beforeEach(function () {
    Fixtures::seedPermissions();
    $this->customerId = Fixtures::customer();
    $this->userId = Fixtures::customerUser($this->customerId);
    $this->requestId = Fixtures::screeningRequest($this->customerId, $this->userId);
    $this->identityTypeId = Fixtures::identityType();
    $this->candidateId = Fixtures::candidate($this->requestId, $this->identityTypeId);
});

it('reports orphaned files but leaves referenced ones alone', function () {
    Storage::fake('client_local');
    Storage::fake('local');

    $referenced = "candidate-documents/{$this->customerId}/REQ/candidate-{$this->candidateId}/nric.jpg";
    Storage::disk('client_local')->put($referenced, 'x');
    DB::table('candidate_documents')->insert([
        'request_candidate_id' => $this->candidateId,
        'screening_request_id' => $this->requestId,
        'type' => 'nric', 'file_path' => $referenced, 'original_name' => 'n.jpg',
        'created_at' => now(), 'updated_at' => now(),
    ]);

    $orphan = "candidate-documents/{$this->customerId}/REQ/candidate-{$this->candidateId}/orphan.pdf";
    Storage::disk('client_local')->put($orphan, 'left-behind');

    $this->artisan('pii:reconcile-files')->assertSuccessful();

    // Dry run: nothing deleted
    Storage::disk('client_local')->assertExists($orphan);
    Storage::disk('client_local')->assertExists($referenced);
});

it('deletes orphans with --delete and keeps referenced files', function () {
    Storage::fake('client_local');
    Storage::fake('local');

    $referenced = "candidate-documents/{$this->customerId}/keep.jpg";
    Storage::disk('client_local')->put($referenced, 'x');
    DB::table('consent_records')->insert([
        'request_candidate_id' => $this->candidateId,
        'consented_at' => now(), 'consent_version' => 'v1', 'consent_text_snapshot' => 'c',
        'evidence_type' => 'digital_form', 'evidence_file_path' => $referenced,
        'created_at' => now(), 'updated_at' => now(),
    ]);

    $orphan = "candidate-documents/{$this->customerId}/gone.pdf";
    Storage::disk('client_local')->put($orphan, 'left-behind');

    $this->artisan('pii:reconcile-files --delete')->assertSuccessful();

    Storage::disk('client_local')->assertMissing($orphan);
    Storage::disk('client_local')->assertExists($referenced); // consent-referenced, kept

    expect(AdminAuditLog::where('action', 'storage.orphan_files_deleted')->exists())->toBeTrue();
});

it('sweeps finance/compliance prefixes too (receipts, payment slips)', function () {
    Storage::fake('client_local');
    Storage::fake('local');

    // A referenced receipt (kept) and an orphaned one (removed).
    $invoiceId = Fixtures::invoice($this->customerId);
    $keptReceipt = "receipts/{$this->customerId}/{$invoiceId}/keep.pdf";
    Storage::disk('client_local')->put($keptReceipt, 'x');
    DB::table('invoice_payment_receipts')->insert([
        'invoice_id' => $invoiceId, 'file_path' => $keptReceipt, 'file_name' => 'keep.pdf',
        'status' => 'pending', 'amount_claimed' => 100,
        'created_at' => now(), 'updated_at' => now(),
    ]);
    $orphanReceipt = "receipts/{$this->customerId}/{$invoiceId}/orphan.pdf";
    Storage::disk('client_local')->put($orphanReceipt, 'left-behind');

    // A referenced payment slip (kept).
    $keptSlip = "payment-slips/{$this->customerId}/slip.pdf";
    Storage::disk('client_local')->put($keptSlip, 'x');
    DB::table('screening_requests')->where('id', $this->requestId)->update(['payment_slip_path' => $keptSlip]);
    $orphanSlip = "payment-slips/{$this->customerId}/orphan.pdf";
    Storage::disk('client_local')->put($orphanSlip, 'left-behind');

    $this->artisan('pii:reconcile-files --delete')->assertSuccessful();

    Storage::disk('client_local')->assertMissing($orphanReceipt);
    Storage::disk('client_local')->assertMissing($orphanSlip);
    Storage::disk('client_local')->assertExists($keptReceipt);
    Storage::disk('client_local')->assertExists($keptSlip);
});

it('never touches report PDFs', function () {
    Storage::fake('local');

    $report = "reports/{$this->requestId}/full-v1.pdf";
    Storage::disk('local')->put($report, 'immutable-report');

    $this->artisan('pii:reconcile-files --delete')->assertSuccessful();

    Storage::disk('local')->assertExists($report); // reports/ prefix is not swept
});
