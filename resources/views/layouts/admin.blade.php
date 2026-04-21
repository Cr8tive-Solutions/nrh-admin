<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'NRH Admin') — NRH Intelligence</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-gray-50 text-gray-900 antialiased" x-data>

<div class="flex h-screen overflow-hidden">

    {{-- ===================== SIDEBAR ===================== --}}
    <aside class="w-64 bg-slate-900 flex flex-col flex-shrink-0 overflow-y-auto">

        {{-- Brand --}}
        <div class="bg-slate-950 px-5 py-4 flex-shrink-0">
            <div class="text-white font-bold text-base tracking-tight">NRH Admin</div>
            <div class="text-slate-500 text-xs mt-0.5">Intelligence Sdn. Bhd.</div>
        </div>

        {{-- Navigation --}}
        <nav class="flex-1 px-3 py-4 space-y-5">

            {{-- Operations --}}
            <div>
                <div class="text-slate-500 uppercase text-[10px] font-semibold px-2 mb-1 tracking-widest">Operations</div>

                <a href="{{ route('dashboard') }}"
                   class="flex items-center gap-2.5 px-3 py-2 rounded-md text-sm font-medium transition-colors
                          {{ request()->routeIs('dashboard') ? 'bg-slate-700 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                    Dashboard
                </a>

                <a href="{{ route('requests.index') }}"
                   class="flex items-center gap-2.5 px-3 py-2 rounded-md text-sm font-medium transition-colors
                          {{ request()->routeIs('requests.*') ? 'bg-slate-700 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    Request Queue
                </a>

                <a href="{{ route('customers.index') }}"
                   class="flex items-center gap-2.5 px-3 py-2 rounded-md text-sm font-medium transition-colors
                          {{ request()->routeIs('customers.*') ? 'bg-slate-700 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    Customers
                </a>
            </div>

            {{-- Finance --}}
            <div>
                <div class="text-slate-500 uppercase text-[10px] font-semibold px-2 mb-1 tracking-widest">Finance</div>

                <a href="{{ route('pricing.index') }}"
                   class="flex items-center gap-2.5 px-3 py-2 rounded-md text-sm font-medium transition-colors
                          {{ request()->routeIs('pricing.*') ? 'bg-slate-700 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                    Scope Pricing
                </a>

                <a href="{{ route('invoices.index') }}"
                   class="flex items-center gap-2.5 px-3 py-2 rounded-md text-sm font-medium transition-colors
                          {{ request()->routeIs('invoices.*') ? 'bg-slate-700 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Invoices
                </a>

                <a href="{{ route('transactions.index') }}"
                   class="flex items-center gap-2.5 px-3 py-2 rounded-md text-sm font-medium transition-colors
                          {{ request()->routeIs('transactions.*') ? 'bg-slate-700 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    Transactions
                </a>
            </div>

            {{-- Configuration --}}
            <div>
                <div class="text-slate-500 uppercase text-[10px] font-semibold px-2 mb-1 tracking-widest">Configuration</div>

                <a href="{{ route('config.scopes.index') }}"
                   class="flex items-center gap-2.5 px-3 py-2 rounded-md text-sm font-medium transition-colors
                          {{ request()->routeIs('config.scopes.*') ? 'bg-slate-700 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                    Scope Types
                </a>

                <a href="{{ route('config.countries.index') }}"
                   class="flex items-center gap-2.5 px-3 py-2 rounded-md text-sm font-medium transition-colors
                          {{ request()->routeIs('config.countries.*') ? 'bg-slate-700 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Countries
                </a>
            </div>

            {{-- Admin (super_admin only) --}}
            @if(session('admin_role') === 'super_admin')
            <div>
                <div class="text-slate-500 uppercase text-[10px] font-semibold px-2 mb-1 tracking-widest">Admin</div>

                <a href="{{ route('staff.index') }}"
                   class="flex items-center gap-2.5 px-3 py-2 rounded-md text-sm font-medium transition-colors
                          {{ request()->routeIs('staff.*') ? 'bg-slate-700 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                    Staff Accounts
                </a>
            </div>
            @endif

        </nav>

        {{-- User info --}}
        <div class="px-4 py-3 border-t border-slate-800 flex-shrink-0">
            <div class="text-slate-300 text-sm font-medium truncate">{{ session('admin_name') }}</div>
            <div class="text-slate-500 text-xs truncate">{{ session('admin_role') }}</div>
            <form method="POST" action="{{ route('logout') }}" class="mt-2">
                @csrf
                <button type="submit" class="text-slate-500 hover:text-slate-300 text-xs transition-colors">
                    Sign out
                </button>
            </form>
        </div>
    </aside>

    {{-- ===================== MAIN CONTENT ===================== --}}
    <div class="flex-1 flex flex-col overflow-hidden">

        {{-- Page header --}}
        <header class="bg-white border-b border-gray-200 px-6 py-3 flex items-center justify-between flex-shrink-0">
            <div>
                <h1 class="text-base font-semibold text-gray-900">@yield('page-title')</h1>
                @hasSection('page-subtitle')
                    <p class="text-sm text-gray-500 mt-0.5">@yield('page-subtitle')</p>
                @endif
            </div>
            <div class="flex items-center gap-3">
                @yield('header-actions')
            </div>
        </header>

        {{-- Flash messages --}}
        @if(session('success'))
        <div class="mx-6 mt-4 bg-green-50 border border-green-200 text-green-800 text-sm rounded-md px-4 py-3 flex items-center gap-2"
             x-data="{ show: true }" x-show="show">
            <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
            {{ session('success') }}
            <button @click="show = false" class="ml-auto text-green-600 hover:text-green-800">✕</button>
        </div>
        @endif

        @if(session('error'))
        <div class="mx-6 mt-4 bg-red-50 border border-red-200 text-red-800 text-sm rounded-md px-4 py-3 flex items-center gap-2"
             x-data="{ show: true }" x-show="show">
            <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
            {{ session('error') }}
            <button @click="show = false" class="ml-auto text-red-600 hover:text-red-800">✕</button>
        </div>
        @endif

        @if($errors->any())
        <div class="mx-6 mt-4 bg-red-50 border border-red-200 text-red-800 text-sm rounded-md px-4 py-3">
            <ul class="list-disc list-inside space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        {{-- Content --}}
        <main class="flex-1 overflow-y-auto p-6">
            @yield('content')
        </main>
    </div>

</div>

</body>
</html>
