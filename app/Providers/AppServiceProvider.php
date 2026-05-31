<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        try {
            if (Schema::hasTable('settings')) {
                $settings = DB::table('settings')
                    ->where('key', 'like', 'smtp_%')
                    ->pluck('value', 'key')
                    ->toArray();

                if (!empty($settings) && !empty($settings['smtp_host'] ?? '')) {
                    config([
                        'mail.default' => 'smtp',
                        'mail.mailers.smtp.host' => $settings['smtp_host'],
                        'mail.mailers.smtp.port' => $settings['smtp_port'] ?? config('mail.mailers.smtp.port'),
                        'mail.mailers.smtp.encryption' => $settings['smtp_encryption'] ?? config('mail.mailers.smtp.encryption'),
                        'mail.mailers.smtp.username' => $settings['smtp_username'] ?? config('mail.mailers.smtp.username'),
                        'mail.mailers.smtp.password' => $settings['smtp_password'] ?? config('mail.mailers.smtp.password'),
                        'mail.mailers.smtp.timeout' => 15,
                        'mail.from.address' => $settings['smtp_from_email'] ?? config('mail.from.address'),
                        'mail.from.name' => $settings['smtp_from_name'] ?? config('mail.from.name'),
                    ]);
                }
            }
        } catch (\Throwable $e) {
            // settings table may not exist during first migrations
        }
    }
}
