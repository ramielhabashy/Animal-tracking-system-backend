<?php

namespace App\Providers;

use App\Contracts\DeviceDataProvider;
use App\Models\Device;
use App\Models\Setting;
use App\Services\DeviceProviders\MqttDeviceProvider;
use App\Services\DeviceProviders\SaniDeviceProvider;
use App\Services\DeviceProviders\SimulatedDeviceProvider;
use Illuminate\Support\ServiceProvider;

class DeviceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(DeviceDataProvider::class, function ($app) {
            $driver = Setting::get('device_real_driver', 'sani');

            return match ($driver) {
                'sani' => $app->make(SaniDeviceProvider::class),
                'mqtt' => $app->make(MqttDeviceProvider::class),
                default => $app->make(SimulatedDeviceProvider::class),
            };
        });

        $this->app->bind('simulated-device-provider', function ($app) {
            return $app->make(SimulatedDeviceProvider::class);
        });
    }

    public function boot(): void
    {
        Device::creating(function (Device $device) {
            if (empty($device->data_source)) {
                $device->data_source = 'simulated';
            }
        });
    }
}
