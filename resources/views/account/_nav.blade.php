<style>
    .acc-nav {
        display: inline-flex; gap: 2px;
        background: var(--paper-2);
        border: 1px solid var(--line);
        padding: 4px;
        border-radius: 10px;
        margin-bottom: 18px;
    }
    .acc-nav-link {
        padding: 7px 14px;
        border-radius: 7px;
        font-size: 12px; font-weight: 600;
        color: var(--ink-500);
        background: transparent;
        border: none;
        text-decoration: none;
        transition: all 120ms ease;
        display: inline-flex; align-items: center; gap: 6px;
    }
    .acc-nav-link svg { width: 13px; height: 13px; }
    .acc-nav-link:hover { color: var(--ink-900); }
    .acc-nav-link.active {
        background: var(--card);
        color: var(--emerald-700);
        box-shadow: 0 1px 2px rgba(0,0,0,0.06), inset 0 0 0 1px var(--line);
    }
</style>

<div class="acc-nav">
    <a href="{{ route('account.profile') }}"
       class="acc-nav-link {{ request()->routeIs('account.profile*') ? 'active' : '' }}">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4.4 3.6-8 8-8s8 3.6 8 8"/></svg>
        Profile
    </a>
    <a href="{{ route('account.security') }}"
       class="acc-nav-link {{ request()->routeIs('account.security') ? 'active' : '' }}">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
        Security
    </a>
</div>
