<?php

namespace App\Console\Commands;

use App\Models\Agreement;
use App\Models\Invoice;
use App\Models\ScreeningRequest;
use App\Services\NotificationService;
use Illuminate\Console\Command;

class GenerateSystemNotifications extends Command
{
    protected $signature   = 'notifications:generate';
    protected $description = 'Create system alert notifications: TAT overdue, expiring agreements, overdue invoices.';

    public function handle(): int
    {
        $this->generateTatOverdue();
        $this->generateAgreementExpiry();
        $this->generateInvoiceOverdue();

        $this->info('System notifications generated.');
        return self::SUCCESS;
    }

    private function generateTatOverdue(): void
    {
        // Active requests open for more than 7 calendar days with no resolution
        $threshold = now()->subDays(7);

        ScreeningRequest::whereIn('status', ['new', 'in_progress'])
            ->where('created_at', '<', $threshold)
            ->with('customer')
            ->get()
            ->each(function (ScreeningRequest $req) {
                $days = (int) $req->created_at->diffInDays(now());
                NotificationService::fanOut(
                    type: 'tat_overdue',
                    title: 'Request '.$req->reference.' is overdue',
                    body: 'Request from '.($req->customer?->name ?? 'a customer').
                          ' has been '.strtolower(str_replace('_', ' ', $req->status)).
                          ' for '.$days.' days with no resolution.',
                    link: route('requests.show', $req),
                    reference: 'tat_overdue_'.$req->id,
                );
            });
    }

    private function generateAgreementExpiry(): void
    {
        Agreement::with('customer')
            ->get()
            ->each(function (Agreement $agreement) {
                $days = $agreement->days_left;

                if ($days > 60 || $days < 0) {
                    return;
                }

                $level = $days <= 14 ? 'critical' : 'warning';
                $title = $days === 0
                    ? 'Agreement for '.$agreement->customer?->name.' has expired'
                    : 'Agreement for '.$agreement->customer?->name.' expires in '.$days.' days';

                NotificationService::fanOut(
                    type: 'agreement_expiry',
                    title: $title,
                    body: 'The service agreement expires on '.$agreement->expiry_date->format('d M Y').
                          '. Contact the account manager to arrange renewal.',
                    link: $agreement->customer ? route('customers.show', $agreement->customer) : null,
                    reference: 'agreement_expiry_'.$agreement->id.'_'.$level,
                );
            });
    }

    private function generateInvoiceOverdue(): void
    {
        Invoice::where('status', 'overdue')
            ->with('customer')
            ->get()
            ->each(function (Invoice $invoice) {
                NotificationService::fanOut(
                    type: 'invoice_overdue',
                    title: 'Invoice '.$invoice->number.' is overdue',
                    body: 'Invoice for '.($invoice->customer?->name ?? 'a customer').
                          ' (MYR '.number_format($invoice->total, 2).') passed its due date of '.
                          $invoice->due_at->format('d M Y').'.',
                    link: route('invoices.show', $invoice),
                    reference: 'invoice_overdue_'.$invoice->id,
                );
            });
    }
}
