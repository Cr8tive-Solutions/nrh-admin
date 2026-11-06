<?php

namespace App\Mail;

use App\Models\CustomerUserInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CustomerUserInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  string  $invitationUrl  The accept link carrying the raw token; the
     *                                 invitation row only holds its hash.
     */
    public function __construct(
        public CustomerUserInvitation $invitation,
        public string $invitationUrl,
    ) {}

    public function envelope(): Envelope
    {
        $customer = $this->invitation->customerUser->customer;

        return new Envelope(
            from: new Address(config('mail.from.address'), 'KYRO'),
            subject: "You've been invited to {$customer->name} on KYRO",
        );
    }

    public function content(): Content
    {
        $user = $this->invitation->customerUser;

        return new Content(
            view: 'emails.customer-invitation',
            with: [
                'user' => $user,
                'customer' => $user->customer,
                'invitationUrl' => $this->invitationUrl,
                'expiresAt' => $this->invitation->expires_at,
            ],
        );
    }
}
