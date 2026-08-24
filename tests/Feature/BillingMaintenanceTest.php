<?php

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoicePaymentReceipt;
use App\Models\ScreeningRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\Support\Fixtures;

beforeEach(function () {
    Fixtures::seedPermissions();
    $this->admin = Fixtures::admin(['role' => 'super_admin']);
    $this->customerId = Fixtures::customer();
    $this->as = fn () => Fixtures::actingAs($this, $this->admin);
});

// ── invoices:mark-overdue ────────────────────────────────────────────────────

it('flips unpaid invoices past their due date to overdue', function () {
    $pastDue = Fixtures::invoice($this->customerId, ['due_at' => now()->subDays(3)->toDateString()]);
    $dueToday = Fixtures::invoice($this->customerId, ['due_at' => now()->toDateString()]);
    $future = Fixtures::invoice($this->customerId, ['due_at' => now()->addDays(3)->toDateString()]);
    $paidPast = Fixtures::invoice($this->customerId, ['status' => 'paid', 'due_at' => now()->subDays(3)->toDateString()]);

    $this->artisan('invoices:mark-overdue')->assertSuccessful();

    expect(Invoice::find($pastDue)->status)->toBe('overdue')
        ->and(Invoice::find($dueToday)->status)->toBe('unpaid')
        ->and(Invoice::find($future)->status)->toBe('unpaid')
        ->and(Invoice::find($paidPast)->status)->toBe('paid');
});

it('lets the verify cascade flip an overdue invoice to paid', function () {
    Fixtures::agreement($this->customerId, 'per_request');
    $userId = Fixtures::customerUser($this->customerId);
    $invoiceId = Fixtures::invoice($this->customerId, [
        'status' => 'overdue', 'due_at' => now()->subDays(3)->toDateString(),
    ]);
    $requestId = Fixtures::screeningRequest($this->customerId, $userId, [
        'status' => 'new', 'invoice_id' => $invoiceId,
    ]);
    $receiptId = Fixtures::receipt($invoiceId, ['amount_claimed' => 106.00]);

    ($this->as)()->post(route('payment-receipts.verify', InvoicePaymentReceipt::find($receiptId)))
        ->assertRedirect();

    expect(Invoice::find($invoiceId)->status)->toBe('paid')
        ->and(ScreeningRequest::find($requestId)->status)->toBe('in_progress');
});

// ── Customer::ledgerBalance() ────────────────────────────────────────────────

it('has no prepaid balance without topup or adjustment rows', function () {
    DB::table('transactions')->insert([
        'customer_id' => $this->customerId, 'type' => 'payment', 'amount' => 500,
        'status' => 'completed', 'method' => 'Bank Transfer',
        'created_at' => now(), 'updated_at' => now(),
    ]);

    expect(Customer::find($this->customerId)->ledgerBalance())->toBeNull();
});

it('derives the prepaid balance from topups, adjustments and payments', function () {
    foreach ([
        ['type' => 'topup', 'amount' => 1000, 'status' => 'completed'],
        ['type' => 'adjustment', 'amount' => 50, 'status' => 'completed'],
        ['type' => 'payment', 'amount' => 300, 'status' => 'completed'],
        ['type' => 'topup', 'amount' => 999, 'status' => 'pending'], // ignored
    ] as $txn) {
        DB::table('transactions')->insert($txn + [
            'customer_id' => $this->customerId, 'method' => 'Bank Transfer',
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    expect(Customer::find($this->customerId)->ledgerBalance())->toBe(750.0);
});

// ── Candidate document download ──────────────────────────────────────────────

it('streams a customer-uploaded candidate document via the client_local disk', function () {
    Storage::fake('client_local');
    Storage::fake('local');

    $userId = Fixtures::customerUser($this->customerId);
    $requestId = Fixtures::screeningRequest($this->customerId, $userId);
    $identityTypeId = Fixtures::identityType();
    $candidateId = Fixtures::candidate($requestId, $identityTypeId);

    $path = "candidate-documents/{$this->customerId}/test/nric.pdf";
    Storage::disk('client_local')->put($path, '%PDF-1.4 test');

    $documentId = DB::table('candidate_documents')->insertGetId([
        'request_candidate_id' => $candidateId,
        'screening_request_id' => $requestId,
        'type' => 'nric',
        'file_path' => $path,
        'original_name' => 'my-nric.pdf',
        'created_at' => now(), 'updated_at' => now(),
    ]);

    ($this->as)()
        ->get(route('requests.candidates.documents.download', [ScreeningRequest::find($requestId), $candidateId, $documentId]))
        ->assertOk()
        ->assertDownload('my-nric.pdf');
});

it('404s for a document belonging to another candidate', function () {
    $userId = Fixtures::customerUser($this->customerId);
    $requestId = Fixtures::screeningRequest($this->customerId, $userId);
    $otherRequestId = Fixtures::screeningRequest($this->customerId, $userId);
    $identityTypeId = Fixtures::identityType();
    $candidateId = Fixtures::candidate($requestId, $identityTypeId);
    $otherCandidateId = Fixtures::candidate($otherRequestId, $identityTypeId);

    $documentId = DB::table('candidate_documents')->insertGetId([
        'request_candidate_id' => $otherCandidateId,
        'screening_request_id' => $otherRequestId,
        'type' => 'resume',
        'file_path' => 'candidate-documents/x/resume.pdf',
        'original_name' => 'resume.pdf',
        'created_at' => now(), 'updated_at' => now(),
    ]);

    ($this->as)()
        ->get(route('requests.candidates.documents.download', [ScreeningRequest::find($requestId), $candidateId, $documentId]))
        ->assertNotFound();
});
