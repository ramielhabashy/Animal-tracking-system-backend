<?php

namespace App\Contracts;

use App\Models\Device;

interface DeviceDataProvider
{
    public function fetchData(Device $device): array;

    public function provision(array $data): Device;

    public function testConnection(): bool;

    public function name(): string;
}
