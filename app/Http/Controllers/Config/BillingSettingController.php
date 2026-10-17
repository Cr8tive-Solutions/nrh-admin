<?php

namespace App\Http\Controllers\Config;

use App\Http\Controllers\Controller;
use App\Models\AdminAuditLog;
use App\Models\Setting;
use Illuminate\Http\Request;

/**
 * Billing settings editable by finance — currently just the SST rate, stored
 * in the shared app_settings table and read by both portals.
 */
class BillingSettingController extends Controller
{
    public function index()
    {
        // Read the stored setting live (falling back to the config default) so
        // the page always reflects the current rate — including right after a save.
        $sstPercent = round(Setting::sstRate((float) config('billing.sst_rate', 0.06)) * 100, 2);

        return view('config.billing.index', compact('sstPercent'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            // Percentage, e.g. 6 or 8 or 0. Up to 2 decimals, capped at 100%.
            'sst_percent' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        $old = round(Setting::sstRate((float) config('billing.sst_rate', 0.06)) * 100, 2);
        $new = round((float) $data['sst_percent'], 2);

        // Store as a decimal fraction (0.06) to match config/billing.php semantics.
        Setting::setValue('sst_rate', round($new / 100, 6));

        AdminAuditLog::record('settings.sst_rate.updated', null, [
            'old_percent' => $old,
            'new_percent' => $new,
        ]);

        return redirect()->route('config.billing.index')
            ->with('success', "SST rate updated to {$new}%.");
    }
}
