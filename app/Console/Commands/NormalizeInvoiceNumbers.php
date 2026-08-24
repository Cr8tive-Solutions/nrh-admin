<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use Illuminate\Console\Command;

class NormalizeInvoiceNumbers extends Command
{
    protected $signature = 'invoices:normalize-numbers {--apply : Persist the renumbering (default is a dry run)}';

    protected $description = 'Pad legacy short invoice numbers (INV-YYYY-N…) to the canonical 4-digit format.';

    public function handle(): int
    {
        $legacy = Invoice::whereRaw("number ~ '^INV-[0-9]{4}-[0-9]{1,3}$'")
            ->orderBy('number')
            ->get();

        if ($legacy->isEmpty()) {
            $this->info('No legacy short invoice numbers found — nothing to do.');

            return self::SUCCESS;
        }

        $rows = [];
        $renamed = 0;

        foreach ($legacy as $invoice) {
            [$prefix, $year, $seq] = explode('-', $invoice->number);
            $new = "{$prefix}-{$year}-".str_pad($seq, 4, '0', STR_PAD_LEFT);

            if (Invoice::where('number', $new)->exists()) {
                $rows[] = [$invoice->number, $new, 'SKIPPED — target exists'];

                continue;
            }

            $rows[] = [$invoice->number, $new, $this->option('apply') ? 'renamed' : 'would rename'];

            if ($this->option('apply')) {
                $invoice->update(['number' => $new]);
                $renamed++;
            }
        }

        $this->table(['Current', 'New', 'Result'], $rows);

        if ($this->option('apply')) {
            $this->info("{$renamed} invoice(s) renumbered.");
            $this->warn('Note: previously issued PDFs and receipts still show the old number.');
        } else {
            $this->comment('Dry run — re-run with --apply to persist. Numbers on already-issued PDFs/receipts will NOT change.');
        }

        return self::SUCCESS;
    }
}
