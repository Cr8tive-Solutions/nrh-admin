<?php

use App\Models\DataSubjectRequest;
use App\Models\Invoice;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\Support\Fixtures;

/**
 * Timestamps are stored and computed in UTC and only converted to the display
 * timezone (Asia/Kuala_Lumpur, UTC+8) when shown, or when taking "today" for a
 * business date. Between 00:00 and 08:00 Malaysian time the UTC date is still
 * yesterday, so that window is where a missed conversion shows up.
 */
beforeEach(function () {
    Fixtures::seedPermissions();
    $this->admin = Fixtures::admin(['role' => 'super_admin']);
    $this->customerId = Fixtures::customer();
    $this->userId = Fixtures::customerUser($this->customerId);
    $this->countryId = Fixtures::country();
    $this->identityTypeId = Fixtures::identityType();
    $this->as = fn () => Fixtures::actingAs($this, $this->admin);
});

afterEach(function () {
    Carbon::setTestNow();
});

it('converts a UTC timestamp to Malaysian time without changing the original', function () {
    $stored = Carbon::parse('2026-11-05 21:54:00', 'UTC');

    $shown = $stored->inDisplayTz();

    expect($shown->format('Y-m-d H:i'))->toBe('2026-11-06 05:54')
        ->and($shown->getTimezone()->getName())->toBe('Asia/Kuala_Lumpur')
        ->and($stored->format('Y-m-d H:i e'))->toBe('2026-11-05 21:54 UTC');
});

it('keeps the app and database session on UTC', function () {
    expect(config('app.timezone'))->toBe('UTC')
        ->and(DB::selectOne('show timezone')->TimeZone)->toBe('UTC');
});

it('numbers invoices and data subject requests by the Malaysian year on New Year morning', function () {
    // 04:00 on 1 Jan 2027 in Malaysia, still 2026 in UTC.
    Carbon::setTestNow(Carbon::parse('2026-12-31 20:00:00', 'UTC'));

    $number = DB::transaction(fn () => Invoice::generateNumber());

    expect($number)->toStartWith('INV-2027-')
        ->and(DataSubjectRequest::nextReference())->toStartWith('DSR-2027-');
});

it('bills requests into the invoice period by their Malaysian date', function () {
    $scopeId = Fixtures::scopeType($this->countryId, ['price' => 100.00]);
    $request = function (string $createdAtUtc, string $reference) use ($scopeId) {
        $requestId = Fixtures::screeningRequest($this->customerId, $this->userId, [
            'status' => 'complete', 'reference' => $reference, 'created_at' => $createdAtUtc,
        ]);
        Fixtures::attachScope(Fixtures::candidate($requestId, $this->identityTypeId), $scopeId);
    };
    // 1 Nov 04:00 in Malaysia — November, though still 31 Oct in UTC.
    $request('2026-10-31 20:00:00', 'REP-TZ-NOV');
    // 1 Dec 01:00 in Malaysia — December, though still 30 Nov in UTC.
    $request('2026-11-30 17:00:00', 'REP-TZ-DEC');

    $json = ($this->as)()->getJson(route('invoices.preview-items', [
        'customer_id' => $this->customerId,
        'period_start' => '2026-11-01',
        'period_end' => '2026-11-30',
    ]))->assertOk()->json();

    expect(collect($json['requests'])->pluck('reference')->all())->toBe(['REP-TZ-NOV']);
});

it('counts requests on the dashboard chart by their Malaysian day', function () {
    // Friday 6 Nov 06:00 in Malaysia.
    Carbon::setTestNow(Carbon::parse('2026-11-05 22:00:00', 'UTC'));
    // Friday 6 Nov 05:00 in Malaysia, still Thursday in UTC.
    Fixtures::screeningRequest($this->customerId, $this->userId, ['created_at' => '2026-11-05 21:00:00']);

    ($this->as)()->get(route('dashboard'))
        ->assertOk()
        ->assertViewHas('weeklyVolume', function ($weeklyVolume) {
            $today = $weeklyVolume->last();

            return $today['label'] === 'Fri' && $today['count'] >= 1;
        });
});

it('converts every stored timestamp a view formats', function () {
    $unconverted = [];

    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator(resource_path('views'))) as $file) {
        if (! str_ends_with($file->getFilename(), '.blade.php')) {
            continue;
        }

        foreach (file($file->getPathname()) as $index => $line) {
            // A *_at attribute or now() formatted directly, without ->inDisplayTz() first.
            if (preg_match('/(\w_at\)?|\bnow\(\))\??->(?!inDisplayTz\()(?:\w+\([^)]*\)->)*(format|isoFormat|toDateString)\(/', $line)) {
                $unconverted[] = str_replace(resource_path('views').'/', '', $file->getPathname()).':'.($index + 1);
            }
        }
    }

    expect($unconverted)->toBe([]);
});
