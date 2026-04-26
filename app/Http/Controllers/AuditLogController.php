<?php

namespace App\Http\Controllers;

use App\Models\AdminAuditLog;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $query = AdminAuditLog::with(['actor', 'target'])->latest('created_at');

        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        $logs = $query->paginate(50)->withQueryString();

        $actions = AdminAuditLog::query()
            ->select('action')
            ->distinct()
            ->orderBy('action')
            ->pluck('action');

        return view('audit.index', compact('logs', 'actions'));
    }
}
