@if ($paginator->hasPages())
<nav role="navigation" aria-label="Pagination" style="display:flex; align-items:center; justify-content:space-between; gap:12px; margin-top:16px;">

    <p style="font-size:11px; color:var(--ink-500); font-family:'JetBrains Mono',monospace; white-space:nowrap; letter-spacing:0.05em;">
        @if ($paginator->firstItem())
            {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} <span style="color:var(--ink-300);">of</span> {{ $paginator->total() }}
        @else
            {{ $paginator->count() }} results
        @endif
    </p>

    <div style="display:flex; align-items:center; gap:3px;">

        {{-- Prev --}}
        @if ($paginator->onFirstPage())
        <span style="display:inline-flex;align-items:center;justify-content:center;width:30px;height:30px;border-radius:6px;border:1px solid var(--line);color:var(--ink-300);font-size:14px;cursor:default;user-select:none;">‹</span>
        @else
        <a href="{{ $paginator->previousPageUrl() }}" rel="prev" style="display:inline-flex;align-items:center;justify-content:center;width:30px;height:30px;border-radius:6px;border:1px solid var(--line);background:var(--card);color:var(--ink-700);font-size:14px;text-decoration:none;transition:border-color 120ms,color 120ms;" onmouseover="this.style.borderColor='var(--emerald-600)';this.style.color='var(--emerald-700)';" onmouseout="this.style.borderColor='var(--line)';this.style.color='var(--ink-700)';">‹</a>
        @endif

        {{-- Page numbers --}}
        @foreach ($elements as $element)
            @if (is_string($element))
            <span style="display:inline-flex;align-items:center;justify-content:center;width:30px;height:30px;font-size:11px;color:var(--ink-400);font-family:'JetBrains Mono',monospace;">…</span>
            @endif
            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                    <span aria-current="page" style="display:inline-flex;align-items:center;justify-content:center;width:30px;height:30px;border-radius:6px;background:var(--emerald-700);color:#fff;font-size:11px;font-weight:600;font-family:'JetBrains Mono',monospace;">{{ $page }}</span>
                    @else
                    <a href="{{ $url }}" aria-label="Page {{ $page }}" style="display:inline-flex;align-items:center;justify-content:center;width:30px;height:30px;border-radius:6px;border:1px solid var(--line);background:var(--card);color:var(--ink-700);font-size:11px;font-family:'JetBrains Mono',monospace;text-decoration:none;transition:border-color 120ms,color 120ms;" onmouseover="this.style.borderColor='var(--emerald-600)';this.style.color='var(--emerald-700)';" onmouseout="this.style.borderColor='var(--line)';this.style.color='var(--ink-700)';">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

        {{-- Next --}}
        @if ($paginator->hasMorePages())
        <a href="{{ $paginator->nextPageUrl() }}" rel="next" style="display:inline-flex;align-items:center;justify-content:center;width:30px;height:30px;border-radius:6px;border:1px solid var(--line);background:var(--card);color:var(--ink-700);font-size:14px;text-decoration:none;transition:border-color 120ms,color 120ms;" onmouseover="this.style.borderColor='var(--emerald-600)';this.style.color='var(--emerald-700)';" onmouseout="this.style.borderColor='var(--line)';this.style.color='var(--ink-700)';">›</a>
        @else
        <span style="display:inline-flex;align-items:center;justify-content:center;width:30px;height:30px;border-radius:6px;border:1px solid var(--line);color:var(--ink-300);font-size:14px;cursor:default;user-select:none;">›</span>
        @endif

    </div>
</nav>
@endif
