<?php

namespace App\Mail;

use App\Models\UserInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class UserInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public UserInvitation $invitation,
        public string $acceptUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'You\'ve been invited to join Oasis Trace',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.user-invitation',
            with: [
                'invitation' => $this->invitation,
                'acceptUrl' => $this->acceptUrl,
                'role' => $this->invitation->role,
            ],
        );
    }
}
