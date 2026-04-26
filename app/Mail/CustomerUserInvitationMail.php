<?php

namespace App\Mail;

use App\Models\CustomerUserInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CustomerUserInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public CustomerUserInvitation $invitation)
    {
    }

    public function envelope(): Envelope
    {
        $customer = $this->invitation->customerUser->customer;

        return new Envelope(
            subject: "You've been invited to {$customer->name} on NRH Intelligence",
        );
    }

    public function content(): Content
    {
        $user = $this->invitation->customerUser;

        return new Content(
            view: 'emails.customer-invitation',
            with: [
                'user'         => $user,
                'customer'     => $user->customer,
                'invitationUrl' => $this->invitation->url(),
                'expiresAt'    => $this->invitation->expires_at,
            ],
        );
    }
}
