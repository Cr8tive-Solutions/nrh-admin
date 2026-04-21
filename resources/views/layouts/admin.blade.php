<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'NRH Admin') — NRH Intelligence</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Inter:wght@300;400;500;600&family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-surface text-on-surface antialiased selection:bg-primary-container selection:text-on-primary-container" x-data>

<div class="flex h-screen overflow-hidden">

    {{-- ===================== SIDEBAR ===================== --}}
    <aside class="w-64 flex flex-col flex-shrink-0 border-r border-outline-variant/30 bg-surface-container-low transition-colors duration-300 overflow-y-auto">
        <div class="p-6 flex flex-col gap-8 h-full">

            {{-- Brand --}}
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-primary flex items-center justify-center flex-shrink-0">
                    <span class="material-symbols-outlined text-white" style="font-size:16px;">shield</span>
                </div>
                <div>
                    <h1 class="font-display font-extrabold text-on-surface text-base tracking-tighter leading-tight">NRH Intelligence</h1>
                    <p class="text-[9px] uppercase tracking-[0.2em] font-bold text-on-surface-variant">Admin Portal</p>
                </div>
            </div>

            {{-- Navigation --}}
            <nav class="flex-1 space-y-0.5">

                {{-- OPERATIONS --}}
                <div class="text-[9px] font-bold uppercase tracking-[0.2em] text-on-surface-variant/60 px-4 pt-1 pb-2">Operations</div>

                <a href="{{ route('dashboard') }}"
                   class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-semibold font-display tracking-tight transition-all
                          {{ request()->routeIs('dashboard') ? 'text-primary bg-primary/10 border-r-4 border-primary scale-95' : 'text-on-surface-variant hover:text-on-surface hover:bg-surface-container' }}">
                    <span class="material-symbols-outlined">dashboard</span>
                    Dashboard
                </a>

                <a href="{{ route('requests.index') }}"
                   class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-semibold font-display tracking-tight transition-all
                          {{ request()->routeIs('requests.*') ? 'text-primary bg-primary/10 border-r-4 border-primary scale-95' : 'text-on-surface-variant hover:text-on-surface hover:bg-surface-container' }}">
                    <span class="material-symbols-outlined">pending_actions</span>
                    Request Queue
                </a>

                <a href="{{ route('customers.index') }}"
                   class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-semibold font-display tracking-tight transition-all
                          {{ request()->routeIs('customers.*') ? 'text-primary bg-primary/10 border-r-4 border-primary scale-95' : 'text-on-surface-variant hover:text-on-surface hover:bg-surface-container' }}">
                    <span class="material-symbols-outlined">corporate_fare</span>
                    Customers
                </a>

                {{-- FINANCE --}}
                <div class="text-[9px] font-bold uppercase tracking-[0.2em] text-on-surface-variant/60 px-4 pt-4 pb-2">Finance</div>

                <a href="{{ route('pricing.index') }}"
                   class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-semibold font-display tracking-tight transition-all
                          {{ request()->routeIs('pricing.*') ? 'text-primary bg-primary/10 border-r-4 border-primary scale-95' : 'text-on-surface-variant hover:text-on-surface hover:bg-surface-container' }}">
                    <span class="material-symbols-outlined">sell</span>
                    Scope Pricing
                </a>

                <a href="{{ route('invoices.index') }}"
                   class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-semibold font-display tracking-tight transition-all
                          {{ request()->routeIs('invoices.*') ? 'text-primary bg-primary/10 border-r-4 border-primary scale-95' : 'text-on-surface-variant hover:text-on-surface hover:bg-surface-container' }}">
                    <span class="material-symbols-outlined">receipt_long</span>
                    Invoices
                </a>

                <a href="{{ route('transactions.index') }}"
                   class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-semibold font-display tracking-tight transition-all
                          {{ request()->routeIs('transactions.*') ? 'text-primary bg-primary/10 border-r-4 border-primary scale-95' : 'text-on-surface-variant hover:text-on-surface hover:bg-surface-container' }}">
                    <span class="material-symbols-outlined">payments</span>
                    Transactions
                </a>

                {{-- CONFIGURATION --}}
                <div class="text-[9px] font-bold uppercase tracking-[0.2em] text-on-surface-variant/60 px-4 pt-4 pb-2">Configuration</div>

                <a href="{{ route('config.scopes.index') }}"
                   class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-semibold font-display tracking-tight transition-all
                          {{ request()->routeIs('config.scopes.*') ? 'text-primary bg-primary/10 border-r-4 border-primary scale-95' : 'text-on-surface-variant hover:text-on-surface hover:bg-surface-container' }}">
                    <span class="material-symbols-outlined">checklist</span>
                    Scope Types
                </a>

                <a href="{{ route('config.countries.index') }}"
                   class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-semibold font-display tracking-tight transition-all
                          {{ request()->routeIs('config.countries.*') ? 'text-primary bg-primary/10 border-r-4 border-primary scale-95' : 'text-on-surface-variant hover:text-on-surface hover:bg-surface-container' }}">
                    <span class="material-symbols-outlined">public</span>
                    Countries
                </a>

                {{-- ADMIN --}}
                @if(session('admin_role') === 'super_admin')
                <div class="text-[9px] font-bold uppercase tracking-[0.2em] text-on-surface-variant/60 px-4 pt-4 pb-2">Admin</div>

                <a href="{{ route('staff.index') }}"
                   class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-semibold font-display tracking-tight transition-all
                          {{ request()->routeIs('staff.*') ? 'text-primary bg-primary/10 border-r-4 border-primary scale-95' : 'text-on-surface-variant hover:text-on-surface hover:bg-surface-container' }}">
                    <span class="material-symbols-outlined">manage_accounts</span>
                    Staff Accounts
                </a>
                @endif
            </nav>

            {{-- Bottom --}}
            <div class="mt-auto pt-5 border-t border-outline-variant/30 space-y-1">
                <a href="{{ route('customers.create') }}"
                   class="block w-full text-center bg-primary text-on-primary py-2.5 px-4 rounded-lg font-display font-bold text-sm mb-3 shadow-md transition-transform active:scale-95 hover:opacity-90">
                    New Customer
                </a>
                <div class="text-sm font-semibold text-on-surface mb-1 px-1">{{ session('admin_name') }}</div>
                <div class="text-[10px] text-on-surface-variant uppercase tracking-wider px-1 mb-2">{{ str_replace('_', ' ', session('admin_role')) }}</div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="flex items-center gap-3 px-4 py-2 text-on-surface-variant hover:text-on-surface transition-all text-sm font-semibold w-full rounded-lg hover:bg-surface-container">
                        <span class="material-symbols-outlined">logout</span> Sign Out
                    </button>
                </form>
            </div>
        </div>
    </aside>

    {{-- ===================== MAIN AREA ===================== --}}
    <div class="flex-1 flex flex-col overflow-hidden">

        {{-- Top App Bar --}}
        <header class="flex justify-between items-center w-full px-8 py-3 sticky top-0 z-30 bg-surface/70 backdrop-blur-md shadow-sm shadow-outline-variant/20 border-b border-outline-variant/20 flex-shrink-0">
            <div class="flex items-center gap-8 flex-1">
                {{-- Search --}}
                <div class="relative w-full max-w-sm">
                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant" style="font-size:18px;">search</span>
                    <input type="text"
                           placeholder="Search requests, customers…"
                           class="w-full bg-surface-container border-none rounded-lg pl-10 pr-4 py-2 text-sm focus:ring-2 focus:ring-primary/20 placeholder:text-on-surface-variant/60 text-on-surface">
                </div>
                {{-- Page title --}}
                <div>
                    <span class="font-display font-bold text-sm text-on-surface">@yield('page-title')</span>
                    @hasSection('page-subtitle')
                        <span class="text-on-surface-variant text-sm"> / @yield('page-subtitle')</span>
                    @endif
                </div>
            </div>

            <div class="flex items-center gap-3">
                {{-- Header actions slot --}}
                @yield('header-actions')

                <div class="h-7 w-px bg-outline-variant mx-1"></div>

                {{-- Notifications --}}
                <div class="relative">
                    <button class="bg-surface-container-highest p-2 rounded-full hover:scale-105 transition-transform">
                        <span class="material-symbols-outlined text-on-surface" style="font-size:18px;">notifications</span>
                    </button>
                    @if(isset($stats) && ($stats['flagged_cases'] ?? 0) > 0)
                    <span class="absolute top-0 right-0 w-2.5 h-2.5 bg-error rounded-full border-2 border-surface"></span>
                    @endif
                </div>

                {{-- User --}}
                <div class="flex items-center gap-2.5">
                    <div class="text-right">
                        <p class="text-xs font-bold text-on-surface leading-tight">{{ session('admin_name') }}</p>
                        <p class="text-[10px] text-on-surface-variant font-medium">{{ ucfirst(str_replace('_', ' ', session('admin_role'))) }}</p>
                    </div>
                    <div class="w-9 h-9 rounded-full bg-primary-container flex items-center justify-center border-2 border-surface-container-highest shadow-sm">
                        <span class="text-on-primary font-display font-bold text-sm">{{ strtoupper(substr(session('admin_name', 'A'), 0, 1)) }}</span>
                    </div>
                </div>
            </div>
        </header>

        {{-- Flash messages --}}
        @if(session('success'))
        <div x-data="{ show: true }" x-show="show"
             class="mx-8 mt-4 bg-secondary-container border border-outline-variant/30 text-on-surface text-sm rounded-lg px-4 py-3 flex items-center gap-2 flex-shrink-0">
            <span class="material-symbols-outlined text-primary" style="font-size:16px;">check_circle</span>
            {{ session('success') }}
            <button @click="show = false" class="ml-auto text-on-surface-variant hover:text-on-surface">
                <span class="material-symbols-outlined" style="font-size:16px;">close</span>
            </button>
        </div>
        @endif

        @if(session('error'))
        <div x-data="{ show: true }" x-show="show"
             class="mx-8 mt-4 bg-error-container/20 border border-error/20 text-error text-sm rounded-lg px-4 py-3 flex items-center gap-2 flex-shrink-0">
            <span class="material-symbols-outlined" style="font-size:16px;">error</span>
            {{ session('error') }}
            <button @click="show = false" class="ml-auto">
                <span class="material-symbols-outlined" style="font-size:16px;">close</span>
            </button>
        </div>
        @endif

        @if($errors->any())
        <div class="mx-8 mt-4 bg-error-container/20 border border-error/20 text-error text-sm rounded-lg px-4 py-3 flex-shrink-0">
            <ul class="list-disc list-inside space-y-0.5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        {{-- Page content --}}
        <main class="flex-1 overflow-y-auto p-8">
            @yield('content')
        </main>
    </div>

</div>

{{-- FAB --}}
<div class="fixed bottom-8 right-8 z-50">
    <a href="{{ route('requests.index', ['status' => 'flagged']) }}"
       class="group flex items-center w-14 h-14 rounded-full bg-primary text-on-primary shadow-2xl hover:scale-110 active:scale-95 transition-all overflow-hidden hover:w-auto hover:px-5 hover:rounded-full gap-0 hover:gap-2">
        <span class="material-symbols-outlined text-2xl flex-shrink-0 group-hover:rotate-90 transition-transform" style="font-size:22px; margin-left: auto; margin-right: auto;">flag</span>
        <span class="max-w-0 group-hover:max-w-xs transition-all overflow-hidden whitespace-nowrap font-display font-bold text-sm">Flagged Cases</span>
    </a>
</div>

</body>
</html>
