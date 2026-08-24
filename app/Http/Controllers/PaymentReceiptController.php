<?php

namespace App\Http\Controllers;

use App\Models\AdminAuditLog;
use App\Models\InvoicePaymentReceipt;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Admin-side review of customer-uploaded payment receipts.
 *
 * Verify path:
 *   1. Mark receipt verified (with admin id + verified_at + optional note).
 *   2. Write a transactions row (type=payment) tied to the invoice.
 *   3. If verified receipts now cover the invoice total, flip invoice → paid.
 *   4. If invoice flipped to paid, cascade-flip every linked screening_request
 *      from 'new' to 'in_progress' so the TAT clock starts.
 *   5. Audit log: payment.verified.
 *
 * Reject path: status='rejected' + verification_note. No downstream effects.
 *
 * Idempotent: verify + reject are both no-ops once the receipt has left
 * 'pending' — prevents the retroactive-reject footgun called out in handoff #3.
 */
class PaymentReceiptController extends Controller
{
    public function verify(Request $request, InvoicePaymentReceipt $receipt)
    {
        if (! $receipt->isPending()) {
            return back()->with('error', 'This receipt has already been '.$receipt->status.' — action ignored.');
        }

        $data = $request->validate([
            'verification_note' => 'nullable|string|max:1000',
        ]);

        DB::transaction(function () use ($receipt, $data) {
            $invoice = $receipt->invoice;

            // Coverage contributed by receipts verified BEFORE this one.
            $alreadyCovered = $invoice->verifiedReceiptsTotal();

            // A receipt with no claimed amount means "I paid what's outstanding"
            // (typical for "I paid the whole bill" uploads; equals the full total
            // when nothing else has been verified yet).
            //
            // The figure is PERSISTED onto the receipt rather than used only for
            // the transaction row: verifiedReceiptsTotal() sums `amount_claimed`,
            // so leaving it NULL would book a full-value payment while
            // contributing 0 to coverage — the invoice would never flip to paid
            // and any request gated on it would stay stuck at 'new'.
            $amount = $receipt->amount_claimed !== null
                ? (float) $receipt->amount_claimed
                : max(0.0, round((float) $invoice->total - $alreadyCovered, 2));

            $receipt->update([
                'status' => 'verified',
                'amount_claimed' => $amount,
                'verified_by_admin_id' => current_admin()?->id,
                'verified_at' => now(),
                'verification_note' => $data['verification_note'] ?? null,
            ]);

            Transaction::create([
                'customer_id' => $invoice->customer_id,
                'type' => 'payment',
                'amount' => $amount,
                'reference' => $invoice->number,
                'status' => 'completed',
                'method' => 'Bank Transfer',
                'notes' => "Receipt #{$receipt->id} verified for invoice {$invoice->number}.",
            ]);

            // Refresh + check coverage. We re-read invoice from DB after the
            // receipt update so verifiedReceiptsTotal() picks up the new row.
            $invoice->refresh();
            $coverage = $invoice->verifiedReceiptsTotal();

            if ($coverage + 0.005 >= (float) $invoice->total && $invoice->status !== 'paid') {
                $invoice->update(['status' => 'paid']);

                // Cascade: any cash-billed requests gated on this invoice now unblock.
                $invoice->screeningRequests()
                    ->where('status', 'new')
                    ->get()
                    ->each(function ($req) {
                        $req->update(['status' => 'in_progress']);
                    });
            }

            AdminAuditLog::record('payment.verified', null, [
                'receipt_id' => $receipt->id,
                'invoice_id' => $invoice->id,
                'invoice_no' => $invoice->number,
                'customer_id' => $invoice->customer_id,
                'amount' => $amount,
                'note' => $data['verification_note'] ?? null,
                'invoice_now_paid' => $invoice->status === 'paid',
            ]);
        });

        return back()->with('success', 'Receipt verified.');
    }

    public function reject(Request $request, InvoicePaymentReceipt $receipt)
    {
        if (! $receipt->isPending()) {
            return back()->with('error', 'This receipt has already been '.$receipt->status.' — action ignored.');
        }

        $data = $request->validate([
            'verification_note' => 'required|string|max:1000',
        ], [
            'verification_note.required' => 'A reason is required when rejecting a receipt.',
        ]);

        $receipt->update([
            'status' => 'rejected',
            'verified_by_admin_id' => current_admin()?->id,
            'verified_at' => now(),
            'verification_note' => $data['verification_note'],
        ]);

        AdminAuditLog::record('payment.rejected', null, [
            'receipt_id' => $receipt->id,
            'invoice_id' => $receipt->invoice_id,
            'invoice_no' => $receipt->invoice?->number,
            'customer_id' => $receipt->invoice?->customer_id,
            'note' => $data['verification_note'],
        ]);

        return back()->with('success', 'Receipt rejected.');
    }

    /** Stream the uploaded receipt file privately to admins. */
    public function downloadFile(InvoicePaymentReceipt $receipt)
    {
        // Receipts are uploaded by the client portal onto its own private disk,
        // reachable here via the client_local mount. Check the admin disk too
        // in case a file was ever stored locally.
        $disk = null;
        if ($receipt->file_path) {
            foreach (['client_local', 'local'] as $candidate) {
                if (Storage::disk($candidate)->exists($receipt->file_path)) {
                    $disk = $candidate;
                    break;
                }
            }
        }

        abort_if($disk === null, 404);

        return Storage::disk($disk)->download(
            $receipt->file_path,
            $receipt->file_name ?: ('receipt-'.$receipt->id.'.'.pathinfo($receipt->file_path, PATHINFO_EXTENSION))
        );
    }
}
