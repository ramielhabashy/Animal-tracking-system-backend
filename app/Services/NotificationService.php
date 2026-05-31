<?php

namespace App\Services;

use App\Models\GeofenceAlert;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class NotificationService
{
    protected function getSetting(string $key, $default = null)
    {
        $setting = DB::table('settings')->where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }

    public function sendGeofenceAlert(GeofenceAlert $alert): array
    {
        Log::info("Geofence alert #{$alert->id} triggered but SMS/WhatsApp/Call are not configured.");

        $alert->update([
            'notification_sent' => true,
            'notification_sent_at' => now(),
        ]);

        return [
            'success' => true,
            'message' => 'Alert logged. SMS/WhatsApp/Call not available.',
        ];
    }
}
