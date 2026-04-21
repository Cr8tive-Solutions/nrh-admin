<?php

namespace App\Http\Controllers;

use App\Models\ScreeningRequest;
use Illuminate\Http\Request;

class RequestQueueController extends Controller
{
    public function index(Request $request)
    {
        $query = ScreeningRequest::with('customer')->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('reference', 'ilike', "%{$search}%")
                  ->orWhereHas('customer', fn ($c) => $c->where('name', 'ilike', "%{$search}%"));
            });
        }

        $requests = $query->paginate(25)->withQueryString();

        return view('requests.index', compact('requests'));
    }

    public function show(ScreeningRequest $screeningRequest)
    {
        $screeningRequest->load(['customer', 'customerUser', 'candidates.identityType', 'candidates.scopeTypes']);
        return view('requests.show', ['request' => $screeningRequest]);
    }

    public function updateStatus(Request $request, ScreeningRequest $screeningRequest)
    {
        $data = $request->validate([
            'status' => 'required|in:new,in_progress,flagged,complete',
        ]);

        $screeningRequest->update(['status' => $data['status']]);

        return back()->with('success', 'Request status updated.');
    }

    public function updateCandidateStatus(Request $request, ScreeningRequest $screeningRequest, int $candidateId)
    {
        $data = $request->validate([
            'status' => 'required|in:new,in_progress,flagged,complete',
        ]);

        $screeningRequest->candidates()->findOrFail($candidateId)->update(['status' => $data['status']]);

        return back()->with('success', 'Candidate status updated.');
    }
}
