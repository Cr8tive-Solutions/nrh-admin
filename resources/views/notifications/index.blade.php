@extends('layouts.admin')

@section('title', 'Notifications')

@section('content')
<div class="page-head">
    <div>
        <h1>Notifications</h1>
        <div class="sub">
            @if ($unreadCount > 0)
                <b>{{ $unreadCount }}</b> unread ·
            @endif
            {{ $notifications->total() }} total
        </div>
    </div>
    @if ($unreadCount > 0)
        <form method="POST" action="{{ route('notifications.read-all') }}">
            @csrf
            <button type="submit" class="btn btn-ghost" style="font-size:13px;">Mark all as read</button>
        </form>
    @endif
</div>

@if ($notifications->isEmpty())
    <div class="card" style="padding:80px 20px;text-align:center;">
        <svg style="width:44px;height:44px;color:var(--ink-300);margin:0 auto 16px;display:block;" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0"/>
        </svg>
        <p style="font-size:13px;color:var(--ink-400);margin:0;">No notifications yet.</p>
    </div>
@else
    <div style="display:flex;flex-direction:column;gap:8px;max-width:800px;">

        @foreach ($notifications as $notif)
            @php
                $borderColor = match($notif->type) {
                    'tat_overdue', 'invoice_overdue' => 'rgba(196,69,58,0.35)',
                    'agreement_expiry'               => 'rgba(184,147,31,0.35)',
                    'new_request'                    => 'rgba(5,150,105,0.3)',
                    'payment_slip'                   => 'rgba(59,130,246,0.3)',
                    default                          => 'var(--line)',
                };
                $bgColor = match($notif->type) {
                    'tat_overdue', 'invoice_overdue' => 'rgba(196,69,58,0.04)',
                    'agreement_expiry'               => 'rgba(184,147,31,0.04)',
                    'new_request'                    => 'rgba(5,150,105,0.04)',
                    'payment_slip'                   => 'rgba(59,130,246,0.04)',
                    default                          => 'var(--card)',
                };
                $iconColor = match($notif->type) {
                    'tat_overdue', 'invoice_overdue' => 'var(--danger)',
                    'agreement_expiry'               => 'var(--gold-600)',
                    'new_request'                    => 'var(--emerald-700)',
                    'payment_slip'                   => '#3b82f6',
                    default                          => 'var(--ink-400)',
                };
                $iconBg = match($notif->type) {
                    'tat_overdue', 'invoice_overdue' => 'rgba(196,69,58,0.1)',
                    'agreement_expiry'               => 'rgba(184,147,31,0.1)',
                    'new_request'                    => 'var(--emerald-50)',
                    'payment_slip'                   => 'rgba(59,130,246,0.1)',
                    default                          => 'var(--paper)',
                };
                $typeLabel = match($notif->type) {
                    'tat_overdue'      => 'TAT Overdue',
                    'invoice_overdue'  => 'Invoice Overdue',
                    'agreement_expiry' => 'Agreement Expiry',
                    'new_request'      => 'New Request',
                    'payment_slip'     => 'Payment Slip',
                    default            => 'Alert',
                };
            @endphp

            <div style="display:flex;align-items:flex-start;gap:16px;padding:16px 20px;background:{{ $bgColor }};border:1px solid {{ $borderColor }};border-left:3px solid {{ $borderColor }};border-radius:var(--radius);{{ ! $notif->isRead() ? 'box-shadow:0 0 0 3px rgba(5,150,105,0.06);' : 'opacity:0.8;' }}">

                {{-- Icon --}}
                <div style="width:36px;height:36px;border-radius:var(--radius);background:{{ $iconBg }};display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:1px;">
                    @if (in_array($notif->type, ['tat_overdue', 'invoice_overdue']))
                        <svg style="width:16px;height:16px;color:{{ $iconColor }};" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/></svg>
                    @elseif ($notif->type === 'agreement_expiry')
                        <svg style="width:16px;height:16px;color:{{ $iconColor }};" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.955 11.955 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z"/></svg>
                    @elseif ($notif->type === 'new_request')
                        <svg style="width:16px;height:16px;color:{{ $iconColor }};" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2M9 5a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2M9 5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2M12 11v4M10 13h4"/></svg>
                    @else
                        <svg style="width:16px;height:16px;color:{{ $iconColor }};" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/></svg>
                    @endif
                </div>

                {{-- Content --}}
                <div style="flex:1;min-width:0;">
                    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;flex-wrap:wrap;">
                        <p style="font-size:13px;font-weight:600;color:var(--ink-900);margin:0;">
                            {{ $notif->title }}
                            @if (! $notif->isRead())
                                <span style="display:inline-block;width:6px;height:6px;border-radius:50%;background:var(--emerald-600);margin-left:6px;vertical-align:middle;" aria-hidden="true"></span>
                            @endif
                        </p>
                        <div style="display:flex;align-items:center;gap:10px;flex-shrink:0;">
                            <span style="font-size:10px;font-weight:600;letter-spacing:0.05em;text-transform:uppercase;color:{{ $iconColor }};background:{{ $iconBg }};padding:2px 7px;border-radius:4px;">{{ $typeLabel }}</span>
                            <span style="font-size:11px;color:var(--ink-400);font-family:var(--font-mono);">{{ $notif->created_at->format('d M Y') }}</span>
                        </div>
                    </div>
                    <p style="font-size:12px;color:var(--ink-600);margin:4px 0 0;line-height:1.5;">{{ $notif->body }}</p>
                    <div style="display:flex;align-items:center;gap:16px;margin-top:10px;">
                        @if ($notif->link)
                            <a href="{{ $notif->link }}" style="display:inline-flex;align-items:center;gap:4px;font-size:12px;font-weight:600;color:var(--emerald-700);text-decoration:none;">
                                View details
                                <svg style="width:12px;height:12px;" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg>
                            </a>
                        @endif
                        @if (! $notif->isRead())
                            <form method="POST" action="{{ route('notifications.read', $notif->id) }}" style="margin:0;">
                                @csrf
                                <button type="submit" style="background:none;border:none;padding:0;font-size:12px;color:var(--ink-400);cursor:pointer;text-decoration:underline;text-underline-offset:2px;">
                                    Mark as read
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach

    </div>

    <div style="margin-top:20px;">
        {{ $notifications->links() }}
    </div>
@endif
@endsection
