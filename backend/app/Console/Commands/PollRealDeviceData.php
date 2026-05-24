<?php

namespace App\Console\Commands;

use App\Contracts\DeviceDataProvider;
use App\Models\Device;
use App\Models\LocationHistory;
use App\Models\Setting;
use Illuminate\Console\Command;

class PollRealDeviceData extends Command
{
    protected $signature = 'devices:poll-real-data';
    protected $description = 'Poll real device data from configured provider (e.g. Sani)';

    public function handle(DeviceDataProvider $provider): int
    {
        if (!Setting::getBoolean('device_real_data_enabled', false)) {
            $this->info('Real device data polling is disabled');
            return Command::SUCCESS;
        }

        $devices = Device::whereNotNull('animal_id')
            ->where('data_source', 'real')
            ->with('animal')
            ->get();

        $count = 0;
        foreach ($devices as $device) {
            try {
                $data = $provider->fetchData($device);

                if (empty($data)) {
                    continue;
                }

                $updateData = [];
                foreach (['gps_lat', 'gps_lng', 'temperature', 'battery_level', 'signal_strength', 'speed', 'status', 'last_ping'] as $field) {
                    if (array_key_exists($field, $data)) {
                        $updateData[$field] = $data[$field];
                    }
                }

                if (!empty($updateData)) {
                    $device->update($updateData);
                }

                if (isset($data['gps_lat']) && isset($data['gps_lng'])) {
                    LocationHistory::create([
                        'device_id' => $device->id,
                        'animal_id' => $device->animal_id,
                        'latitude' => $data['gps_lat'],
                        'longitude' => $data['gps_lng'],
                        'speed' => $data['speed'] ?? null,
                        'recorded_at' => now(),
                        'data_source' => 'real',
                    ]);
                }

                $count++;
            } catch (\Throwable $e) {
                $this->warn("Failed to poll device {$device->device_id}: {$e->getMessage()}");
            }
        }

        $this->info("Polled {$count} real devices at " . now()->format('Y-m-d H:i:s'));
        return Command::SUCCESS;
    }
}
