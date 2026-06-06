<?php

namespace App\Mail;

use App\Models\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $logoUrl;
    public string $platformName;

    public function __construct(
        public $subject,
        public $greeting = 'Hello,',
        public array $lines = [],
        public ?string $actionUrl = null,
        public ?string $actionText = null,
        public ?string $footerText = null,
    ) {
        // Fetch brand settings from DB so emails always use the latest logo & name
        try {
            $this->logoUrl = Setting::get('general_logo', '');
            $this->platformName = Setting::get('general_platform_name', 'Oasis Trace');

            // Build full URL if logo is a relative path
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
            subject: $this->subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.notification',
        );
    }
}
