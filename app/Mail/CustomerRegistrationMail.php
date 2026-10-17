<?php

namespace App\Mail;

use App\Models\CustomerRegistration;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * The three emails of the invite-to-register flow, sharing one template:
 *   invite   — link to the registration form ($url = the tokenised link)
 *   approved — account is live ($url = client portal sign-in)
 *   rejected — not approved, with staff's reason
 */
class CustomerRegistrationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public CustomerRegistration $registration,
        public string $kind,
        public ?string $url = null,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: match ($this->kind) {
            'invite' => "You're invited to register on NRH Intelligence",
            'approved' => 'Your NRH Intelligence account is approved',
            'rejected' => 'Your NRH Intelligence registration',
        });
    }

    public function content(): Content
    {
        $r = $this->registration;
        $company = e($r->company_name ?: 'your organisation');

        $with = match ($this->kind) {
            'invite' => [
                'title' => 'Register your organisation',
                'heading' => $r->contact_name ? 'Hello, '.$r->contact_name.'.' : 'Hello.',
                'paragraphs' => [
                    'NRH Intelligence has invited you to register '.$company.' on our client portal for background screening services.',
                    'Click the button below to enter your company and contact details and choose a password. Our team will verify your details and email you once your account is approved.',
                ],
                'buttonLabel' => 'Register now',
                'note' => '<strong style="color:#666;">Security info</strong><br>This link is personal to <strong>'.e($r->email).'</strong> and expires on <strong>'
                    .$r->expires_at->format('d M Y, H:i').'</strong>. If you were not expecting this invitation, you can safely ignore this email.',
            ],
            'approved' => [
                'title' => 'Account approved',
                'heading' => 'Welcome aboard, '.$r->contact_name.'.',
                'paragraphs' => [
                    'Your registration for <strong style="color:#1a1a1a;">'.$company.'</strong> has been verified and approved.',
                    'You can now sign in to the client portal with <strong>'.e($r->email).'</strong> and the password you chose when registering.',
                ],
                'buttonLabel' => 'Sign in',
                'note' => null,
            ],
            'rejected' => [
                'title' => 'Registration update',
                'heading' => 'Hello, '.$r->contact_name.'.',
                'paragraphs' => array_values(array_filter([
                    'Thank you for registering '.$company.' with NRH Intelligence. Unfortunately we were unable to approve the registration at this time.',
                    $r->review_note ? '<strong style="color:#1a1a1a;">Reason:</strong> '.nl2br(e($r->review_note)) : null,
                    'If you believe this is a mistake or would like to provide more information, please reply to this email.',
                ])),
                'buttonLabel' => null,
                'note' => null,
            ],
        };

        return new Content(
            view: 'emails.customer-registration',
            with: $with + ['buttonUrl' => $this->url],
        );
    }
}
