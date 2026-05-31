<?php
$user = App\Models\User::where('email','galal@oasis.com')->first();
echo "User: $user->name (id=$user->id)\n";
$animals = App\Models\Animal::where('owner_id', $user->id)->get();
echo "Animals: " . $animals->count() . "\n";
foreach ($animals as $a) {
    $dev = $a->device;
    echo "  Animal #$a->id: " . ($a->name ?: $a->animal_id) . " ($a->animal_id) | device_id=" . ($dev->id ?? 'NONE') . " | gps_lat=" . ($dev->gps_lat ?? 'null') . " | gps_lng=" . ($dev->gps_lng ?? 'null') . " | battery=" . ($dev->battery_level ?? 'null') . " | last_ping=" . ($dev->last_ping ?? 'null') . "\n";
}
