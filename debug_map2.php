<?php
$devices = App\Models\Device::whereIn('id', [10, 11])->get();
foreach ($devices as $d) {
    echo "Device #$d->id: $d->device_id ($d->name) | type=$d->type | status=$d->status | gps_lat=$d->gps_lat | gps_lng=$d->gps_lng | battery=$d->battery_level | last_ping=$d->last_ping\n";
}
echo "\nSample AnimalResource output for animal 17:\n";
$animal = App\Models\Animal::with(['owner','device','groups','geofences'])->find(17);
if ($animal) {
    $res = new App\Http\Resources\AnimalResource($animal);
    echo json_encode($res->toArray(request()), JSON_PRETTY_PRINT) . "\n";
}
