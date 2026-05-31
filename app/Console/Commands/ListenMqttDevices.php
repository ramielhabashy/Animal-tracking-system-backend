<?php

namespace App\Console\Commands;

use App\Models\Device;
use App\Models\LocationHistory;
use App\Models\Setting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use PhpMqtt\Client\ConnectionSettings;
use PhpMqtt\Client\MqttClient;

class ListenMqttDevices extends Command
{
    protected $signature = 'devices:mqtt-listen';
    protected $description = 'Subscribe to MQTT topics for real-time Sani device telemetry';

    public function handle(): int
    {
        if (!Setting::getBoolean('device_mqtt_enabled', false)) {
            $this->warn('MQTT listener is disabled. Enable via Device Integration settings.');
            return Command::SUCCESS;
        }

        $host = Setting::get('device_mqtt_broker_host', '');
        $port = (int) Setting::get('device_mqtt_broker_port', 1883);
        $username = Setting::get('device_mqtt_username', '');
        $password = Setting::get('device_mqtt_password', '');
        $topicPrefix = Setting::get('device_mqtt_topic_prefix', 'sani');

        if (empty($host)) {
            $this->error('MQTT broker host not configured.');
            return Command::FAILURE;
        }

        $clientId = 'oasis-listener-' . gethostname();
        $this->info("Connecting to MQTT broker at {$host}:{$port}...");

        try {
            $client = new MqttClient($host, $port, $clientId);

            $connectionSettings = (new ConnectionSettings)
                ->setKeepAliveInterval(60)
                ->setConnectTimeout(30);

            if (!empty($username)) {
                $connectionSettings->setAuthenticationCredentials($username, $password);
            }

            $client->connect($connectionSettings);
            $this->info('Connected. Subscribing to telemetry topics...');

            $telemetryTopic = "{$topicPrefix}/+/telemetry";
            $statusTopic = "{$topicPrefix}/+/status";

            $client->subscribe($telemetryTopic, function (string $topic, string $message) {
                $this->processTelemetry($topic, $message);
            }, 0);

            $client->subscribe($statusTopic, function (string $topic, string $message) {
                $this->processStatus($topic, $message);
            }, 0);

            $this->info("Listening on {$telemetryTopic} and {$statusTopic}. Press Ctrl+C to stop.");

            $client->loop(true);

        } catch (\Throwable $e) {
            $this->error("MQTT listener error: {$e->getMessage()}");
            Log::error("ListenMqttDevices: {$e->getMessage()}");
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    protected function processTelemetry(string $topic, string $message): void
    {
        $parts = explode('/', $topic);
        $deviceId = $parts[1] ?? null;

        if (!$deviceId) {
            return;
        }

        $device = Device::where('device_id', $deviceId)->where('data_source', 'real')->first();
        if (!$device) {
            return;
        }

        try {
            $data = json_decode($message, true);
            if (!$data || !is_array($data)) {
                return;
            }

            $updateData = [];
            foreach (['gps_lat', 'gps_lng', 'temperature', 'battery_level', 'signal_strength', 'speed', 'status', 'last_ping'] as $field) {
                if (array_key_exists($field, $data)) {
                    $updateData[$field] = $data[$field];
                }
            }

            $updateData['last_ping'] = now();

            if (!empty($updateData)) {
                $device->update($updateData);
            }

            if (isset($data['gps_lat'], $data['gps_lng'])) {
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

            $lat = $data['gps_lat'] ?? '?';
            $lng = $data['gps_lng'] ?? '?';
            $temp = $data['temperature'] ?? '?';
            $this->line("  [telemetry] {$deviceId}: lat={$lat}, lng={$lng}, temp={$temp}");
        } catch (\Throwable $e) {
            Log::error("ListenMqttDevices: failed to process telemetry for {$deviceId}: {$e->getMessage()}");
        }
    }

    protected function processStatus(string $topic, string $message): void
    {
        $parts = explode('/', $topic);
        $deviceId = $parts[1] ?? null;

        if (!$deviceId) {
            return;
        }

        $device = Device::where('device_id', $deviceId)->where('data_source', 'real')->first();
        if (!$device) {
            return;
        }

        try {
            $data = json_decode($message, true);
            $status = is_array($data) ? ($data['status'] ?? $message) : trim($message);

            $device->update([
                'status' => $status,
                'last_ping' => now(),
            ]);

            $this->line("  [status] {$deviceId}: {$status}");
        } catch (\Throwable $e) {
            Log::error("ListenMqttDevices: failed to process status for {$deviceId}: {$e->getMessage()}");
        }
    }
}
