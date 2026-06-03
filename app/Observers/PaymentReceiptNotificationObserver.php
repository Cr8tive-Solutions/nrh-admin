<?php

namespace App\Observers;

use App\Models\InvoicePaymentReceipt;
use App\Services\NotificationService;

class PaymentReceiptNotificationObserver
{
    public function created(InvoicePaymentReceipt $receipt): void
    {
        $invoice = $receipt->invoice;
        $customer = $invoice?->customer;

        NotificationService::fanOut(
            type: 'payment_slip',
            title: 'Payment slip uploaded — pending verification',
            body: 'A payment slip for '.($invoice ? 'invoice '.$invoice->number : 'an invoice').
                  ' from '.($customer?->name ?? 'a customer').
                  ' (MYR '.number_format($receipt->amount_claimed, 2).') needs verification.',
            link: $invoice ? route('invoices.show', $invoice) : null,
            reference: 'payment_slip_'.$receipt->id,
        );
    }
}
