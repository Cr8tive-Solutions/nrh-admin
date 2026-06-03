<?php

namespace App\Observers;

use App\Models\ScreeningRequest;
use App\Services\NotificationService;

class ScreeningRequestNotificationObserver
{
    public function created(ScreeningRequest $request): void
    {
        NotificationService::fanOut(
            type: 'new_request',
            title: 'New screening request submitted',
            body: 'Request '.$request->reference.' was submitted by '.($request->customer?->name ?? 'a customer').'. Assign and start verification.',
            link: route('requests.show', $request),
            reference: 'new_request_'.$request->id,
        );
    }
}
