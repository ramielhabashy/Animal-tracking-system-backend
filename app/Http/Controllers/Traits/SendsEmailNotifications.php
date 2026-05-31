<?php

namespace App\Http\Controllers\Traits;

use App\Mail\NotificationMail;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

trait SendsEmailNotifications
{
    protected function shouldSendEmail(string $type): bool
    {
        try {
            if (!DB::getSchemaBuilder()->hasTable('settings')) {
                return true;
            }
            $pref = DB::table('settings')
                ->where('key', 'email_notify_' . $type)
                ->value('value');
            return $pref === null ? true : filter_var($pref, FILTER_VALIDATE_BOOLEAN);
        } catch (\Throwable $e) {
            return true;
        }
    }

    protected function sendNotificationMail(
        User $user,
        string $notificationType,
        string $subject,
        array $lines = [],
        ?string $actionUrl = null,
        ?string $actionText = null,
        ?string $greeting = null,
    ): void {
        if (empty($user->email) || !$this->shouldSendEmail($notificationType)) {
            return;
        }

        try {
            Mail::to($user->email)->send(new NotificationMail(
                subject: $subject,
                greeting: $greeting ?? "Dear {$user->name},",
                lines: $lines,
                actionUrl: $actionUrl,
                actionText: $actionText,
                footerText: config('mail.from.name', 'Oasis Trace') . ' Team',
            ));
        } catch (\Throwable $e) {
            Log::error("Failed to send email to {$user->email}: {$e->getMessage()}");
        }
    }

    protected function sendNotificationMailToEmail(
        string $email,
        string $name,
        string $notificationType,
        string $subject,
        array $lines = [],
        ?string $actionUrl = null,
        ?string $actionText = null,
    ): void {
        if (empty($email) || !$this->shouldSendEmail($notificationType)) {
            return;
        }

        try {
            Mail::to($email)->send(new NotificationMail(
                subject: $subject,
                greeting: "Dear {$name},",
                lines: $lines,
                actionUrl: $actionUrl,
                actionText: $actionText,
                footerText: config('mail.from.name', 'Oasis Trace') . ' Team',
            ));
        } catch (\Throwable $e) {
            Log::error("Failed to send email to {$email}: {$e->getMessage()}");
        }
    }
}
