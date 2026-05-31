<?php

namespace App\Console\Commands;

use App\Models\Device;
use App\Models\GeofenceAlert;
use Illuminate\Console\Command;

class CheckTemperatureAlerts extends Command
{
    protected $signature = 'alerts:check-temperature';
    protected $description = 'Check device temperature readings and create alerts for abnormal temperatures';

    public function handle(): int
    {
        $devices = Device::whereNotNull('animal_id')
            ->whereNotNull('temperature')
            ->with('animal')
            ->get();

        if ($devices->isEmpty()) {
            $this->info('No devices with temperature readings found.');
            return Command::SUCCESS;
        }

        $createdAlerts = 0;

        foreach ($devices as $device) {
            $temperature = (float) $device->temperature;

            if (empty($device->animal)) {
                continue;
            }

            $animalName = $device->animal->name;

            if ($temperature > 39.5) {
                $existing = GeofenceAlert::where('type', 'temperature')
                    ->where('severity', 'High')
                    ->where('animal_id', $device->animal_id)
                    ->where('created_at', '>=', now()->subHours(6))
                    ->first();

                if (!$existing) {
                    GeofenceAlert::create([
                        'type' => 'temperature',
                        'geofence_id' => null,
                        'animal_id' => $device->animal_id,
                        'device_id' => $device->id,
                        'severity' => 'High',
                        'message' => "High temperature alert: {$temperature}°C for {$animalName}",
                        'resolved' => false,
                        'is_acknowledged' => false,
                        'triggered_at' => now(),
                    ]);
                    $createdAlerts++;
                    $this->line("  High temp alert for {$animalName}: {$temperature}°C");
                }
            } elseif ($temperature > 39.0 && $temperature <= 39.5) {
                $existing = GeofenceAlert::where('type', 'temperature')
                    ->where('severity', 'Medium')
                    ->where('animal_id', $device->animal_id)
                    ->where('created_at', '>=', now()->subHours(6))
                    ->first();

                if (!$existing) {
                    GeofenceAlert::create([
                        'type' => 'temperature',
                        'geofence_id' => null,
                        'animal_id' => $device->animal_id,
                        'device_id' => $device->id,
                        'severity' => 'Medium',
                        'message' => "Elevated temperature: {$temperature}°C for {$animalName}",
                        'resolved' => false,
                        'is_acknowledged' => false,
                        'triggered_at' => now(),
                    ]);
                    $createdAlerts++;
                    $this->line("  Medium temp alert for {$animalName}: {$temperature}°C");
                }
            }
        }

        $this->info("Done! Created {$createdAlerts} new temperature alerts.");

        return Command::SUCCESS;
    }
}
