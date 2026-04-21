@extends('layouts.admin')

@section('title', $request->reference)
@section('page-title', $request->reference)
@section('page-subtitle', $request->customer->name ?? '')

@section('header-actions')
    <a href="{{ route('requests.index') }}" class="text-sm text-gray-500 hover:text-gray-700">← Back to Queue</a>
@endsection

@section('content')

<div class="grid grid-cols-3 gap-6">

    {{-- Left: Main info + candidates --}}
    <div class="col-span-2 space-y-4">

        {{-- Request info --}}
        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <div class="grid grid-cols-3 gap-4 text-sm">
                <div>
                    <div class="text-gray-500 text-xs font-medium uppercase">Customer</div>
                    <div class="mt-1 font-medium">
                        <a href="{{ route('customers.show', $request->customer) }}" class="text-emerald-700 hover:text-emerald-900">
                            {{ $request->customer->name ?? '—' }}
                        </a>
                    </div>
                </div>
                <div>
                    <div class="text-gray-500 text-xs font-medium uppercase">Submitted by</div>
                    <div class="mt-1">{{ $request->customerUser->name ?? '—' }}</div>
                </div>
                <div>
                    <div class="text-gray-500 text-xs font-medium uppercase">Type</div>
                    <div class="mt-1">{{ $request->type ?? '—' }}</div>
                </div>
                <div>
                    <div class="text-gray-500 text-xs font-medium uppercase">Submitted</div>
                    <div class="mt-1">{{ $request->created_at->format('d M Y, H:i') }}</div>
                </div>
                <div>
                    <div class="text-gray-500 text-xs font-medium uppercase">Last updated</div>
                    <div class="mt-1">{{ $request->updated_at->format('d M Y, H:i') }}</div>
                </div>
                <div>
                    <div class="text-gray-500 text-xs font-medium uppercase">Candidates</div>
                    <div class="mt-1">{{ $request->candidates->count() }}</div>
                </div>
            </div>
        </div>

        {{-- Candidates --}}
        <div class="bg-white rounded-lg border border-gray-200">
            <div class="px-4 py-3 border-b border-gray-100">
                <h2 class="text-sm font-semibold text-gray-900">Candidates</h2>
            </div>
            @forelse($request->candidates as $candidate)
            <div class="border-b border-gray-100 last:border-0 p-4" x-data="{ open: false }">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <div class="flex items-center gap-3">
                            <span class="font-medium text-gray-900 text-sm">{{ $candidate->name }}</span>
                            <span class="text-gray-500 text-xs font-mono">{{ $candidate->identity_number }}</span>
                            @if($candidate->identityType)
                            <span class="badge badge-gray">{{ $candidate->identityType->name }}</span>
                            @endif
                            <span class="badge {{ $candidate->statusBadgeClass() ?? '' }}">{{ str_replace('_', ' ', $candidate->status) }}</span>
                        </div>
                        @if($candidate->mobile)
                        <div class="text-xs text-gray-400 mt-1">{{ $candidate->mobile }}</div>
                        @endif
                        @if($candidate->remarks)
                        <div class="text-xs text-gray-500 mt-1 italic">{{ $candidate->remarks }}</div>
                        @endif
                    </div>
                    <div class="flex items-center gap-2 ml-4">
                        {{-- Update candidate status --}}
                        <form method="POST" action="{{ route('requests.candidates.status', [$request, $candidate->id]) }}">
                            @csrf @method('PATCH')
                            <select name="status" onchange="this.form.submit()"
                                    class="text-xs border border-gray-300 rounded px-2 py-1 focus:outline-none focus:ring-1 focus:ring-emerald-600">
                                @foreach(['new', 'in_progress', 'flagged', 'complete'] as $s)
                                <option value="{{ $s }}" {{ $candidate->status === $s ? 'selected' : '' }}>{{ str_replace('_', ' ', ucfirst($s)) }}</option>
                                @endforeach
                            </select>
                        </form>
                        <button @click="open = !open" class="text-xs text-emerald-700 hover:text-emerald-900">
                            Scopes <span x-text="open ? '▲' : '▼'"></span>
                        </button>
                    </div>
                </div>

                {{-- Scope checks --}}
                <div x-show="open" x-cloak class="mt-3 pl-2">
                    @if($candidate->scopeTypes->count())
                    <div class="flex flex-wrap gap-1.5">
                        @foreach($candidate->scopeTypes as $scope)
                        <span class="inline-flex items-center gap-1 border border-gray-200 rounded px-2 py-0.5 text-xs text-gray-600">
                            {{ $scope->name }}
                            @if($scope->pivot->status)
                            <span class="w-2 h-2 rounded-full inline-block
                                {{ $scope->pivot->status === 'complete' ? 'bg-emerald-500' : ($scope->pivot->status === 'flagged' ? 'bg-error' : ($scope->pivot->status === 'in_progress' ? 'bg-gold-500' : 'bg-gray-300')) }}">
                            </span>
                            @endif
                        </span>
                        @endforeach
                    </div>
                    @else
                        <p class="text-xs text-gray-400">No scope checks assigned.</p>
                    @endif
                </div>
            </div>
            @empty
            <div class="px-4 py-8 text-center text-gray-400 text-sm">No candidates found.</div>
            @endforelse
        </div>

    </div>

    {{-- Right: Status update --}}
    <div class="space-y-4">
        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <h2 class="text-sm font-semibold text-gray-900 mb-3">Request Status</h2>
            <div class="mb-3">
                <span class="badge {{ $request->statusBadgeClass() }} text-sm">{{ str_replace('_', ' ', $request->status) }}</span>
            </div>
            <form method="POST" action="{{ route('requests.status', $request) }}">
                @csrf @method('PATCH')
                <div class="space-y-2">
                    @foreach(['new' => 'New', 'in_progress' => 'In Progress', 'flagged' => 'Flagged', 'complete' => 'Complete'] as $value => $label)
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="status" value="{{ $value }}"
                               {{ $request->status === $value ? 'checked' : '' }}
                               class="accent-emerald-700">
                        <span class="text-sm text-gray-700">{{ $label }}</span>
                    </label>
                    @endforeach
                </div>
                <button type="submit" class="mt-4 w-full nrh-btn nrh-btn-primary">
                    Update Status
                </button>
            </form>
        </div>

        @if($request->meta)
        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <h2 class="text-sm font-semibold text-gray-900 mb-3">Meta</h2>
            <pre class="text-xs text-gray-600 bg-gray-50 rounded p-2 overflow-x-auto">{{ json_encode($request->meta, JSON_PRETTY_PRINT) }}</pre>
        </div>
        @endif
    </div>

</div>
@endsection
