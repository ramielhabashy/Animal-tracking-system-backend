<?php

namespace App\Mail;

use App\Models\Setting;
use App\Models\UserInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class UserInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $logoUrl;
    public string $platformName;

    public function __construct(
        public UserInvitation $invitation,
        public string $acceptUrl,
    ) {
        try {
            $this->logoUrl = Setting::get('general_logo', '');
            $this->platformName = Setting::get('general_platform_name', 'Oasis Trace');

            if ($this->logoUrl && !str_starts_with($this->logoUrl, 'http')) {
                $appUrl = rtrim(config('app.url', 'http://localhost:8050'), '/');
                $this->logoUrl = $appUrl . '/' . ltrim($this->logoUrl, '/');
            }
        } catch (\Throwable $e) {
            $this->logoUrl = '';
            $this->platformName = 'Oasis Trace';
        }
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'You\'ve been invited to join ' . $this->platformName,
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
