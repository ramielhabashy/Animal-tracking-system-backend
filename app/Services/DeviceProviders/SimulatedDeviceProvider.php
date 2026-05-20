<?php

namespace App\Services\DeviceProviders;

use App\Contracts\DeviceDataProvider;
use App\Models\Device;
use App\Models\LocationHistory;

class SimulatedDeviceProvider implements DeviceDataProvider
{
    public function fetchData(Device $device): array
    {
        $lastLocation = LocationHistory::where('device_id', $device->id)
            ->orderByDesc('recorded_at')
            ->first();

        if ($lastLocation) {
            $latOffset = (rand(-30, 30) / 100000);
            $lngOffset = (rand(-30, 30) / 100000);
            $newLat = $lastLocation->latitude + $latOffset;
            $newLng = $lastLocation->longitude + $lngOffset;
        } else {
            $newLat = $device->gps_lat ?? (24.7136 + (rand(-100, 100) / 10000));
            $newLng = $device->gps_lng ?? (46.6753 + (rand(-100, 100) / 10000));
        }

        $batteryDrain = rand(0, 2);
        $newBattery = max(0, ($device->battery_level ?? 100) - $batteryDrain);

        $status = 'online';
        if ($newBattery < 10) {
            $status = 'offline';
        } elseif ($newBattery < 20 || rand(0, 10) === 0) {
            $status = 'low_signal';
        }

        return [
            'gps_lat' => $newLat,
            'gps_lng' => $newLng,
            'temperature' => $device->temperature,
            'battery_level' => $newBattery,
            'signal_strength' => $device->signal_strength ?? rand(60, 100),
            'speed' => rand(0, 50) / 10,
            'status' => $status,
            'last_ping' => now(),
        ];
    }

    public function provision(array $data): Device
    {
        return Device::create(array_merge($data, [
            'data_source' => 'simulated',
            'status' => 'online',
        ]));
    }

    public function testConnection(): bool
    {
        return true;
    }

    public function name(): string
    {
        return 'Simulated';
    }
}
