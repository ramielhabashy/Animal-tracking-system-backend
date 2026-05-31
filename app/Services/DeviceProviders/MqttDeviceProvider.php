<?php

namespace App\Services\DeviceProviders;

use App\Contracts\DeviceDataProvider;
use App\Models\Device;
use App\Models\Setting;
use Illuminate\Support\Facades\Log;
use PhpMqtt\Client\Facades\MQTT;
use PhpMqtt\Client\MqttClient;

class MqttDeviceProvider implements DeviceDataProvider
{
    protected string $brokerHost;
    protected int $brokerPort;
    protected string $username;
    protected string $password;
    protected string $topicPrefix;

    public function __construct()
    {
        $this->brokerHost = Setting::get('device_mqtt_broker_host', '');
        $this->brokerPort = (int) Setting::get('device_mqtt_broker_port', 1883);
        $this->username = Setting::get('device_mqtt_username', '');
        $this->password = Setting::get('device_mqtt_password', '');
        $this->topicPrefix = Setting::get('device_mqtt_topic_prefix', 'sani');
    }

    public function fetchData(Device $device): array
    {
        return [];
    }

    public function provision(array $data): Device
    {
        $device = Device::create(array_merge($data, [
            'data_source' => 'real',
            'driver' => 'mqtt',
            'status' => 'online',
        ]));

        try {
            if (!empty($this->brokerHost)) {
                $topic = "{$this->topicPrefix}/{$device->device_id}/provision";
                MQTT::publish($topic, json_encode([
                    'device_id' => $device->device_id,
                    'serial_number' => $device->serial_number,
                    'type' => $device->type,
                    'name' => $device->name,
                ]));
            }
        } catch (\Throwable $e) {
            Log::error("MqttDeviceProvider: provision failed for {$device->device_id}: {$e->getMessage()}");
        }

        return $device;
    }

    public function testConnection(): bool
    {
        $host = Setting::get('device_mqtt_broker_host', '');
        $port = (int) Setting::get('device_mqtt_broker_port', 1883);
        $username = Setting::get('device_mqtt_username', '');
        $password = Setting::get('device_mqtt_password', '');

        if (empty($host)) {
            return false;
        }

        try {
            $client = new MqttClient($host, $port, 'oasis-test-' . uniqid());
            if (!empty($username)) {
                $client->connect($username, $password);
            } else {
                $client->connect();
            }
            $client->disconnect();
            return true;
        } catch (\Throwable $e) {
            Log::error("MqttDeviceProvider: test connection failed: {$e->getMessage()}");
            return false;
        }
    }

    public function name(): string
    {
        return 'MQTT';
    }
}
