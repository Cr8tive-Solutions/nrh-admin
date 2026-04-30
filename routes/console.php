<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Daily PDPA retention purge — redacts PII on candidates past the configured retention period.
Schedule::command('pdpa:purge-expired')
    ->dailyAt('02:30')
    ->timezone('Asia/Kuala_Lumpur')
    ->onOneServer();

// Annual holiday sync from Nager.Date — runs Jan 5 at 03:00 to fetch the current year's
// public holidays. Idempotent (skips dates already present), so re-running is safe.
Schedule::command('holidays:sync')
    ->cron('0 3 5 1 *')
    ->timezone('Asia/Kuala_Lumpur')
    ->onOneServer();
