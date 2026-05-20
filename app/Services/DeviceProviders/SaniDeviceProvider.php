<?php

namespace App\Services\DeviceProviders;

use App\Contracts\DeviceDataProvider;
use App\Models\Device;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SaniDeviceProvider implements DeviceDataProvider
{
    protected string $endpoint;
    protected string $apiKey;

    public function __construct()
    {
        $this->endpoint = rtrim(Setting::get('device_real_api_endpoint', ''), '/');
        $this->apiKey = Setting::get('device_real_api_key', '');
    }

    public function fetchData(Device $device): array
    {
        if (empty($this->endpoint) || empty($this->apiKey)) {
            Log::warning('SaniDeviceProvider: API endpoint or key not configured');
            return [];
        }

        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Accept' => 'application/json',
                ])
                ->get("{$this->endpoint}/devices/{$device->device_id}/telemetry");

            if ($response->successful()) {
                $data = $response->json();

                return [
                    'gps_lat' => $data['latitude'] ?? $device->gps_lat,
                    'gps_lng' => $data['longitude'] ?? $device->gps_lng,
                    'temperature' => $data['temperature'] ?? $device->temperature,
                    'battery_level' => $data['battery'] ?? $device->battery_level,
                    'signal_strength' => $data['signal'] ?? $device->signal_strength,
                    'speed' => $data['speed'] ?? $device->speed,
                    'status' => $data['status'] ?? $device->status,
                    'last_ping' => now(),
                ];
            }

            Log::warning("SaniDeviceProvider: API error for {$device->device_id}: {$response->status()}");
            return [];
        } catch (\Throwable $e) {
            Log::error("SaniDeviceProvider: connection failed for {$device->device_id}: {$e->getMessage()}");
            return [];
        }
    }

    public function provision(array $data): Device
    {
        $device = Device::create(array_merge($data, [
            'data_source' => 'real',
            'driver' => 'sani',
            'status' => 'online',
        ]));

        try {
            if (!empty($this->endpoint) && !empty($this->apiKey)) {
                Http::timeout(15)
                    ->withHeaders([
                        'Authorization' => 'Bearer ' . $this->apiKey,
                        'Accept' => 'application/json',
                    ])
                    ->post("{$this->endpoint}/devices/register", [
                        'device_id' => $device->device_id,
                        'serial_number' => $device->serial_number,
                        'type' => $device->type,
                        'name' => $device->name,
                    ]);
            }
        } catch (\Throwable $e) {
            Log::error("SaniDeviceProvider: provision failed for {$device->device_id}: {$e->getMessage()}");
        }

        return $device;
    }

    public function testConnection(): bool
    {
        if (empty($this->endpoint) || empty($this->apiKey)) {
            return false;
        }

        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Accept' => 'application/json',
                ])
                ->get("{$this->endpoint}/health");

            return $response->successful();
        } catch (\Throwable $e) {
            Log::error("SaniDeviceProvider: health check failed: {$e->getMessage()}");
            return false;
        }
    }

    public function name(): string
    {
        return 'Sani';
    }
}
