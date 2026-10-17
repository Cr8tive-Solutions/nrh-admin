@php
    $s = $registration->displayStatus();
    $label = [
        'invited'   => ['Invited', 'badge-blue'],
        'expired'   => ['Link expired', 'badge-red'],
        'submitted' => ['Awaiting review', 'badge-amber'],
        'approved'  => ['Approved', 'badge-green'],
        'rejected'  => ['Rejected', 'badge-red'],
        'cancelled' => ['Cancelled', 'badge-gray'],
    ][$s];
@endphp
<span class="badge {{ $label[1] }}">{{ $label[0] }}</span>
