<?php

use App\Models\Invoice;
use App\Models\InvoicePaymentReceipt;
use App\Models\ScreeningRequest;
use Illuminate\Support\Facades\DB;
use Tests\Support\Fixtures;

beforeEach(function () {
    Fixtures::seedPermissions();
    $this->admin = Fixtures::admin(['role' => 'super_admin']);

    $this->customerId = Fixtures::customer();
    $this->userId = Fixtures::customerUser($this->customerId);
    Fixtures::agreement($this->customerId, 'per_request');

    $this->invoiceId = Fixtures::invoice($this->customerId, [
        'subtotal' => 100.00, 'tax' => 6.00, 'total' => 106.00,
    ]);

    // A cash-billed request gated on this invoice.
    $this->requestId = Fixtures::screeningRequest($this->customerId, $this->userId, [
        'status' => 'new', 'invoice_id' => $this->invoiceId,
    ]);

    $this->as = fn () => Fixtures::actingAs($this, $this->admin);
});

it('runs the full verify cascade when the receipt covers the invoice', function () {
    $receiptId = Fixtures::receipt($this->invoiceId, ['amount_claimed' => 106.00]);

    ($this->as)()->post(route('payment-receipts.verify', InvoicePaymentReceipt::find($receiptId)))
        ->assertRedirect();

    $receipt = InvoicePaymentReceipt::find($receiptId);
    expect($receipt->status)->toBe('verified')
        ->and($receipt->verified_at)->not->toBeNull()
        ->and($receipt->verified_by_admin_id)->toBe($this->admin->id);

    // Invoice flips to paid...
    expect(Invoice::find($this->invoiceId)->status)->toBe('paid');

    // ...which cascades the gated request from new -> in_progress.
    expect(ScreeningRequest::find($this->requestId)->status)->toBe('in_progress');

    // ...and records a payment transaction.
    $tx = DB::table('transactions')->where('customer_id', $this->customerId)->first();
    expect($tx->type)->toBe('payment')
        ->and((float) $tx->amount)->toBe(106.00);
});

it('does not flip the invoice when the receipt only part-covers it', function () {
    $receiptId = Fixtures::receipt($this->invoiceId, ['amount_claimed' => 50.00]);

    ($this->as)()->post(route('payment-receipts.verify', InvoicePaymentReceipt::find($receiptId)));

    expect(InvoicePaymentReceipt::find($receiptId)->status)->toBe('verified')
        ->and(Invoice::find($this->invoiceId)->status)->toBe('unpaid')
        // The gated request must stay blocked until the invoice is fully paid.
        ->and(ScreeningRequest::find($this->requestId)->status)->toBe('new');
});

it('flips the invoice once part payments add up', function () {
    $first = Fixtures::receipt($this->invoiceId, ['amount_claimed' => 50.00]);
    $second = Fixtures::receipt($this->invoiceId, ['amount_claimed' => 56.00]);

    ($this->as)()->post(route('payment-receipts.verify', InvoicePaymentReceipt::find($first)));
    expect(Invoice::find($this->invoiceId)->status)->toBe('unpaid');

    ($this->as)()->post(route('payment-receipts.verify', InvoicePaymentReceipt::find($second)));
    expect(Invoice::find($this->invoiceId)->status)->toBe('paid')
        ->and(ScreeningRequest::find($this->requestId)->status)->toBe('in_progress');
});

/**
 * BUG (documented, not endorsed): a receipt with no `amount_claimed` is treated
 * inconsistently by the two halves of the verify cascade.
 *
 *   PaymentReceiptController::verify() — falls back to the full invoice total,
 *     so it writes a transactions row for RM106 and the audit log records a
 *     full payment.
 *   Invoice::verifiedReceiptsTotal()   — SUMs `amount_claimed`, where NULL
 *     contributes 0, so coverage stays 0.00 and the invoice is never flipped.
 *
 * Net effect: the money was booked, but the invoice stayed 'unpaid' and any
 * cash-billed request gated on it stayed stuck at 'new'. Verify is idempotent,
 * so an admin could not even fix it by re-verifying.
 *
 * FIXED 2026-08-10: verify() now persists the fallback figure into
 * `amount_claimed`, so it contributes to coverage like any other receipt.
 */
it('treats a receipt with no claimed amount as paying the outstanding balance', function () {
    $receiptId = Fixtures::receipt($this->invoiceId, ['amount_claimed' => null]);

    ($this->as)()->post(route('payment-receipts.verify', InvoicePaymentReceipt::find($receiptId)));

    // The fallback figure is written onto the receipt so it counts toward coverage.
    expect((float) InvoicePaymentReceipt::find($receiptId)->amount_claimed)->toBe(106.00)
        ->and(Invoice::find($this->invoiceId)->verifiedReceiptsTotal())->toBe(106.00);

    expect((float) DB::table('transactions')->where('customer_id', $this->customerId)->value('amount'))
        ->toBe(106.00);

    expect(Invoice::find($this->invoiceId)->status)->toBe('paid')
        ->and(ScreeningRequest::find($this->requestId)->status)->toBe('in_progress');
});

it('falls back to the REMAINING balance when earlier part payments exist', function () {
    // 50 already verified, then a no-amount receipt: it must settle the
    // outstanding 56, not re-claim the full 106 and overshoot.
    $first = Fixtures::receipt($this->invoiceId, ['amount_claimed' => 50.00]);
    ($this->as)()->post(route('payment-receipts.verify', InvoicePaymentReceipt::find($first)));

    $second = Fixtures::receipt($this->invoiceId, ['amount_claimed' => null]);
    ($this->as)()->post(route('payment-receipts.verify', InvoicePaymentReceipt::find($second)));

    expect((float) InvoicePaymentReceipt::find($second)->amount_claimed)->toBe(56.00)
        ->and(Invoice::find($this->invoiceId)->verifiedReceiptsTotal())->toBe(106.00)
        ->and(Invoice::find($this->invoiceId)->status)->toBe('paid');
});

it('is idempotent — re-verifying does nothing', function () {
    $receiptId = Fixtures::receipt($this->invoiceId, ['amount_claimed' => 106.00]);
    $receipt = InvoicePaymentReceipt::find($receiptId);

    ($this->as)()->post(route('payment-receipts.verify', $receipt));
    ($this->as)()->post(route('payment-receipts.verify', InvoicePaymentReceipt::find($receiptId)))
        ->assertSessionHas('error');

    // Exactly one transaction — no double-charge.
    expect(DB::table('transactions')->where('customer_id', $this->customerId)->count())->toBe(1);
});

it('refuses to reject an already-verified receipt', function () {
    $receiptId = Fixtures::receipt($this->invoiceId, ['amount_claimed' => 106.00]);

    ($this->as)()->post(route('payment-receipts.verify', InvoicePaymentReceipt::find($receiptId)));
    ($this->as)()->post(route('payment-receipts.reject', InvoicePaymentReceipt::find($receiptId)), [
        'verification_note' => 'changed my mind',
    ])->assertSessionHas('error');

    expect(InvoicePaymentReceipt::find($receiptId)->status)->toBe('verified')
        ->and(Invoice::find($this->invoiceId)->status)->toBe('paid');
});

it('rejects a pending receipt with no downstream effects', function () {
    $receiptId = Fixtures::receipt($this->invoiceId, ['amount_claimed' => 106.00]);

    ($this->as)()->post(route('payment-receipts.reject', InvoicePaymentReceipt::find($receiptId)), [
        'verification_note' => 'Slip is illegible.',
    ]);

    expect(InvoicePaymentReceipt::find($receiptId)->status)->toBe('rejected')
        ->and(Invoice::find($this->invoiceId)->status)->toBe('unpaid')
        ->and(ScreeningRequest::find($this->requestId)->status)->toBe('new')
        ->and(DB::table('transactions')->count())->toBe(0);
});

it('blocks a viewer from verifying receipts', function () {
    $receiptId = Fixtures::receipt($this->invoiceId, ['amount_claimed' => 106.00]);
    $viewer = Fixtures::admin(['role' => 'viewer']);

    Fixtures::actingAs($this, $viewer)
        ->post(route('payment-receipts.verify', InvoicePaymentReceipt::find($receiptId)))
        ->assertForbidden();

    expect(InvoicePaymentReceipt::find($receiptId)->status)->toBe('pending');
});

it('blocks the legacy per-request slip path when an invoice is linked', function () {
    // Requests tied to an invoice must be paid through the receipt flow.
    ($this->as)()->postJson(route('requests.verify-payment', ScreeningRequest::find($this->requestId)))
        ->assertStatus(422);

    expect(ScreeningRequest::find($this->requestId)->status)->toBe('new');
});
