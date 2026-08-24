<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use Illuminate\Console\Command;

class MarkOverdueInvoices extends Command
{
    protected $signature = 'invoices:mark-overdue';

    protected $description = 'Flip unpaid invoices past their due date to overdue.';

    public function handle(): int
    {
        // An invoice is overdue the day AFTER due_at — due today is still on time.
        // The receipt-verify cascade flips overdue → paid the same way it does
        // unpaid → paid, so no reverse transition is needed here.
        $flipped = 0;

        Invoice::where('status', 'unpaid')
            ->whereDate('due_at', '<', today())
            ->get()
            ->each(function (Invoice $invoice) use (&$flipped) {
                $invoice->update(['status' => 'overdue']);
                $flipped++;
            });

        $this->info("{$flipped} invoice(s) marked overdue.");

        return self::SUCCESS;
    }
}
