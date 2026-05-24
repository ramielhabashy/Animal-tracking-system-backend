<?php

namespace Database\Seeders;

use App\Models\{
    User, Animal, Device, AnimalGroup, Geofence,
    MedicalRecord, VaccinationSchedule, Task, PredefinedTask,
    GeofenceAlert, UserSubscription, LocationHistory, Setting,
};
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class FarmSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('=== FarmSeeder: Creating 2 demo farms ===');

        $ownerRole = Role::where('name', 'Owner')->first();
        $shepherdRole = Role::where('name', 'Shepherd')->first();
        $doctorRole = Role::where('name', 'Doctor')->first();
        $managerRole = Role::where('name', 'Manager')->first();

        $starterTier = \App\Models\SubscriptionTier::where('slug', 'starter')->first();
        $enterpriseTier = \App\Models\SubscriptionTier::where('slug', 'enterprise')->first();

        // ──────────────────────────────────────────────
        // FARM 1: Green Valley Ranch — Enterprise
        // ──────────────────────────────────────────────
        $valley = User::updateOrCreate(
            ['email' => 'greenvalley@oasis.com'],
            ['name' => 'Sultan Al-Green', 'password' => Hash::make('password'), 'is_active' => true, 'phone' => '+966511111001']
        );
        $valley->syncRoles([$ownerRole]);

        UserSubscription::create([
            'user_id' => $valley->id,
            'tier_id' => $enterpriseTier?->id ?? 1,
            'status' => 'active',
            'started_at' => now()->subDays(60),
            'ends_at' => now()->addDays(305),
            'billing_cycle' => 'yearly',
            'payment_method' => 'bank_transfer',
            'payment_reference' => 'FARM-VALLEY-ENT-' . $valley->id,
        ]);

        $vShepherd1 = User::updateOrCreate(
            ['email' => 'shepherd.greenvalley@oasis.com'],
            ['name' => 'Khalid Al-Raee', 'password' => Hash::make('password'), 'is_active' => true, 'phone' => '+966511111011', 'managed_by' => $valley->id]
        );
        $vShepherd1->syncRoles([$shepherdRole]);

        $vShepherd2 = User::updateOrCreate(
            ['email' => 'helper.greenvalley@oasis.com'],
            ['name' => 'Majed Al-Saidi', 'password' => Hash::make('password'), 'is_active' => true, 'phone' => '+966511111012', 'managed_by' => $valley->id]
        );
        $vShepherd2->syncRoles([$shepherdRole]);

        $vDoctor = User::updateOrCreate(
            ['email' => 'vet.greenvalley@oasis.com'],
            ['name' => 'Dr. Huda Al-Tabib', 'password' => Hash::make('password'), 'is_active' => true, 'phone' => '+966511111013', 'managed_by' => $valley->id]
        );
        $vDoctor->syncRoles([$doctorRole]);

        $vManager = User::updateOrCreate(
            ['email' => 'manager.greenvalley@oasis.com'],
            ['name' => 'Fahad Al-Mudeer', 'password' => Hash::make('password'), 'is_active' => true, 'phone' => '+966511111014', 'managed_by' => $valley->id]
        );
        $vManager->syncRoles([$managerRole]);

        // Devices for Farm 1
        $vDevices = [];
        $vDeviceData = [
            ['device_id' => 'GVL-0001', 'gps_lat' => 24.8000, 'gps_lng' => 46.7000, 'battery_level' => 95, 'status' => 'online', 'temperature' => 38.5],
            ['device_id' => 'GVL-0002', 'gps_lat' => 24.8010, 'gps_lng' => 46.7010, 'battery_level' => 87, 'status' => 'online', 'temperature' => 38.8],
            ['device_id' => 'GVL-0003', 'gps_lat' => 24.8020, 'gps_lng' => 46.7020, 'battery_level' => 72, 'status' => 'low_signal', 'temperature' => 39.2],
            ['device_id' => 'GVL-0004', 'gps_lat' => 24.8030, 'gps_lng' => 46.7030, 'battery_level' => 91, 'status' => 'online', 'temperature' => 38.3],
            ['device_id' => 'GVL-0005', 'gps_lat' => 24.8040, 'gps_lng' => 46.7040, 'battery_level' => 45, 'status' => 'online', 'temperature' => 38.6],
        ];
        foreach ($vDeviceData as $d) {
            $vDevices[] = Device::create($d);
        }

        // Animals for Farm 1
        $vAnimals = [];
        $vAnimalData = [
            ['animal_id' => 'GVL-001', 'name' => 'Barq', 'species' => 'Camel', 'breed' => 'Majaheem', 'gender' => 'Male', 'date_of_birth' => '2020-03-15', 'color_markings' => 'Dark brown', 'current_weight' => 720, 'baseline_temperature' => 38.4, 'owner_id' => $valley->id],
            ['animal_id' => 'GVL-002', 'name' => 'Ward', 'species' => 'Camel', 'breed' => 'Wadhah', 'gender' => 'Female', 'date_of_birth' => '2021-07-22', 'color_markings' => 'Golden', 'current_weight' => 580, 'baseline_temperature' => 38.2, 'owner_id' => $valley->id],
            ['animal_id' => 'GVL-003', 'name' => 'Shahin', 'species' => 'Camel', 'breed' => 'Suhail', 'gender' => 'Male', 'date_of_birth' => '2022-11-10', 'color_markings' => 'White with brown spots', 'current_weight' => 480, 'baseline_temperature' => 38.9, 'owner_id' => $valley->id],
            ['animal_id' => 'GVL-004', 'name' => 'Najm', 'species' => 'Horse', 'breed' => 'Arabian', 'gender' => 'Male', 'date_of_birth' => '2019-05-08', 'color_markings' => 'Bay', 'current_weight' => 450, 'baseline_temperature' => 37.8, 'owner_id' => $valley->id],
            ['animal_id' => 'GVL-005', 'name' => 'Layla', 'species' => 'Sheep', 'breed' => 'Najdi', 'gender' => 'Female', 'date_of_birth' => '2023-01-20', 'color_markings' => 'White wool', 'current_weight' => 65, 'baseline_temperature' => 39.5, 'owner_id' => $valley->id],
        ];
        foreach ($vAnimalData as $a) {
            $existing = Animal::where('animal_id', $a['animal_id'])->first();
            $vAnimals[] = $existing ? tap($existing)->update($a) : Animal::create($a);
        }
        $vAnimalsByDevice = ['GVL-0001' => 'GVL-001', 'GVL-0002' => 'GVL-002', 'GVL-0003' => 'GVL-003', 'GVL-0004' => 'GVL-004', 'GVL-0005' => 'GVL-005'];
        foreach ($vDevices as $device) {
            $animalKey = $vAnimalsByDevice[$device->device_id] ?? null;
            $animal = $animalKey ? collect($vAnimals)->firstWhere('animal_id', $animalKey) : null;
            $device->animal_id = $animal?->id;
            $device->owner_id = $animal?->owner_id ?? $valley->id;
            $device->save();
        }

        // Groups for Farm 1
        $vGroup1 = AnimalGroup::create(['name' => 'Valley Premium Camels', 'description' => 'Top breeding camels of Green Valley', 'color' => '#10b981', 'owner_id' => $valley->id]);
        $vGroup1->animals()->sync([$vAnimals[0]->id, $vAnimals[1]->id]);
        $vGroup1->shepherds()->sync([$vShepherd1->id, $vShepherd2->id]);

        $vGroup2 = AnimalGroup::create(['name' => 'Valley Racing Team', 'description' => 'Racing camels and horses', 'color' => '#3b82f6', 'owner_id' => $valley->id]);
        $vGroup2->animals()->sync([$vAnimals[2]->id, $vAnimals[3]->id]);
        $vGroup2->shepherds()->sync([$vShepherd1->id]);

        $vGroup3 = AnimalGroup::create(['name' => 'Valley Small Stock', 'description' => 'Sheep and small ruminants', 'color' => '#f59e0b', 'owner_id' => $valley->id]);
        $vGroup3->animals()->sync([$vAnimals[4]->id]);
        $vGroup3->shepherds()->sync([$vShepherd2->id]);

        // Geofences for Farm 1
        $vGeos = [];
        $vGeoData = [
            ['name' => 'Valley North Pasture', 'coordinates' => json_encode([[24.8000, 46.7000], [24.8000, 46.7120], [24.7880, 46.7120], [24.7880, 46.7000], [24.8000, 46.7000]]), 'color' => '#22C55E', 'alert_type' => 'both', 'owner_id' => $valley->id],
            ['name' => 'Valley Racing Track', 'coordinates' => json_encode([[24.8050, 46.7050], [24.8050, 46.7150], [24.7950, 46.7150], [24.7950, 46.7050], [24.8050, 46.7050]]), 'color' => '#F59E0B', 'alert_type' => 'entry', 'owner_id' => $valley->id],
            ['name' => 'Valley Quarantine', 'coordinates' => json_encode([[24.8100, 46.6950], [24.8100, 46.7000], [24.8050, 46.7000], [24.8050, 46.6950], [24.8100, 46.6950]]), 'color' => '#EF4444', 'alert_type' => 'both', 'owner_id' => $valley->id],
        ];
        foreach ($vGeoData as $g) {
            $vGeos[] = Geofence::create($g);
        }
        $vGeos[0]->animals()->sync([$vAnimals[0]->id, $vAnimals[1]->id, $vAnimals[2]->id]);
        $vGeos[0]->groups()->sync([$vGroup1->id]);
        $vGeos[1]->animals()->sync([$vAnimals[2]->id, $vAnimals[3]->id]);
        $vGeos[1]->groups()->sync([$vGroup2->id]);
        $vGeos[2]->animals()->sync([$vAnimals[0]->id]);

        // Location history for Farm 1
        foreach ($vDevices as $device) {
            $animal = $device->animal;
            if (!$animal) continue;
            $lat = $device->gps_lat;
            $lng = $device->gps_lng;
            $heading = rand(0, 360);
            for ($i = 0; $i < 24; $i++) {
                $heading = ($heading + rand(-30, 30) + 360) % 360;
                $dist = rand(30, 150) / 10000;
                $rad = $heading * (M_PI / 180);
                $lat += $dist * cos($rad);
                $lng += $dist * sin($rad);
                LocationHistory::create([
                    'device_id' => $device->id, 'animal_id' => $animal->id,
                    'latitude' => $lat, 'longitude' => $lng,
                    'recorded_at' => now()->subHours($i * 2),
                    'speed' => rand(0, 60) / 10, 'heading' => $heading,
                ]);
            }
            $device->update(['gps_lat' => $lat, 'gps_lng' => $lng]);
        }

        // Medical records for Farm 1
        MedicalRecord::create(['animal_id' => $vAnimals[0]->id, 'owner_id' => $valley->id, 'record_type' => 'checkup', 'title' => "Barq annual checkup", 'description' => 'Full health assessment. Excellent condition.', 'record_date' => now()->subDays(30), 'veterinarian' => 'Dr. Huda Al-Tabib', 'status' => 'completed', 'health_status' => 'excellent']);
        MedicalRecord::create(['animal_id' => $vAnimals[3]->id, 'owner_id' => $valley->id, 'record_type' => 'treatment', 'title' => "Najm hoof care", 'description' => 'Routine hoof trimming and shoe check.', 'record_date' => now()->subDays(10), 'veterinarian' => 'Dr. Huda Al-Tabib', 'status' => 'completed']);

        // Vaccinations for Farm 1
        VaccinationSchedule::create(['animal_id' => $vAnimals[0]->id, 'owner_id' => $valley->id, 'vaccine_name' => 'Clostridial 5-in-1', 'scheduled_date' => now()->subDays(60), 'administered_date' => now()->subDays(60), 'status' => 'administered', 'veterinarian' => 'Dr. Huda Al-Tabib']);
        VaccinationSchedule::create(['animal_id' => $vAnimals[4]->id, 'owner_id' => $valley->id, 'vaccine_name' => 'CD-T', 'scheduled_date' => now()->addDays(20), 'status' => 'scheduled']);

        // Tasks for Farm 1
        Task::create(['owner_id' => $valley->id, 'assigned_to' => $vShepherd1->id, 'animal_id' => $vAnimals[0]->id, 'title' => 'Barq morning feeding', 'description' => 'Feed premium concentrate mix', 'priority' => 'high', 'task_type' => 'feeding', 'status' => 'pending', 'due_date' => now()->addHours(3)]);
        Task::create(['owner_id' => $valley->id, 'assigned_to' => $vShepherd2->id, 'animal_id' => $vAnimals[4]->id, 'title' => 'Layla grazing rotation', 'description' => 'Move sheep to north pasture', 'priority' => 'medium', 'task_type' => 'movement', 'status' => 'in_progress', 'due_date' => now()->addHours(6)]);
        Task::create(['owner_id' => $valley->id, 'assigned_to' => $vShepherd1->id, 'animal_id' => $vAnimals[3]->id, 'title' => 'Najm training session', 'description' => 'Light training and exercise', 'priority' => 'low', 'task_type' => 'other', 'status' => 'completed', 'due_date' => now()->subDays(1), 'completed_at' => now()->subDays(1)]);

        // Alerts for Farm 1
        GeofenceAlert::create(['geofence_id' => $vGeos[2]->id, 'animal_id' => $vAnimals[0]->id, 'device_id' => $vDevices[0]->id, 'type' => 'exit', 'severity' => 'critical', 'message' => 'Barq (GVL-001) has entered Quarantine Zone', 'resolved' => false, 'is_acknowledged' => false, 'latitude' => 24.8070, 'longitude' => 46.6970, 'triggered_at' => now()->subHours(2)]);

        $this->command->info('  Farm 1: Green Valley Ranch (Enterprise) — 5 animals, 3 groups, 3 geofences, 4 staff');

        // ──────────────────────────────────────────────
        // FARM 2: Desert Springs Farm — Starter
        // ──────────────────────────────────────────────
        $springs = User::updateOrCreate(
            ['email' => 'desertsprings@oasis.com'],
            ['name' => 'Noura Al-Sahra', 'password' => Hash::make('password'), 'is_active' => true, 'phone' => '+966522222001']
        );
        $springs->syncRoles([$ownerRole]);

        UserSubscription::create([
            'user_id' => $springs->id,
            'tier_id' => $starterTier?->id ?? 2,
            'status' => 'active',
            'started_at' => now()->subDays(15),
            'ends_at' => now()->addDays(350),
            'billing_cycle' => 'monthly',
            'payment_method' => 'card',
            'payment_reference' => 'FARM-SPRINGS-START-' . $springs->id,
        ]);

        $sShepherd = User::updateOrCreate(
            ['email' => 'shepherd.desertsprings@oasis.com'],
            ['name' => 'Salem Al-Badw', 'password' => Hash::make('password'), 'is_active' => true, 'phone' => '+966522222011', 'managed_by' => $springs->id]
        );
        $sShepherd->syncRoles([$shepherdRole]);

        // Devices for Farm 2
        $sDevices = [];
        $sDeviceData = [
            ['device_id' => 'DSF-0001', 'gps_lat' => 25.1000, 'gps_lng' => 47.2000, 'battery_level' => 82, 'status' => 'online', 'temperature' => 38.7],
            ['device_id' => 'DSF-0002', 'gps_lat' => 25.1010, 'gps_lng' => 47.2010, 'battery_level' => 68, 'status' => 'online', 'temperature' => 39.1],
            ['device_id' => 'DSF-0003', 'gps_lat' => 25.1020, 'gps_lng' => 47.2020, 'battery_level' => 30, 'status' => 'low_signal', 'temperature' => 39.6],
        ];
        foreach ($sDeviceData as $d) {
            $sDevices[] = Device::create($d);
        }

        // Animals for Farm 2
        $sAnimals = [];
        $sAnimalData = [
            ['animal_id' => 'DSF-001', 'name' => 'Reem', 'species' => 'Goat', 'breed' => 'Damascus', 'gender' => 'Female', 'date_of_birth' => '2022-06-15', 'color_markings' => 'Reddish brown', 'current_weight' => 55, 'baseline_temperature' => 39.3, 'owner_id' => $springs->id],
            ['animal_id' => 'DSF-002', 'name' => 'Sawt', 'species' => 'Sheep', 'breed' => 'Awassi', 'gender' => 'Male', 'date_of_birth' => '2023-09-01', 'color_markings' => 'White with black face', 'current_weight' => 85, 'baseline_temperature' => 39.7, 'owner_id' => $springs->id],
            ['animal_id' => 'DSF-003', 'name' => 'Nadim', 'species' => 'Camel', 'breed' => 'Safari', 'gender' => 'Male', 'date_of_birth' => '2021-12-20', 'color_markings' => 'Light brown', 'current_weight' => 510, 'baseline_temperature' => 38.6, 'owner_id' => $springs->id],
        ];
        foreach ($sAnimalData as $a) {
            $existing = Animal::where('animal_id', $a['animal_id'])->first();
            $sAnimals[] = $existing ? tap($existing)->update($a) : Animal::create($a);
        }
        $sAnimalsByDevice = ['DSF-0001' => 'DSF-001', 'DSF-0002' => 'DSF-002', 'DSF-0003' => 'DSF-003'];
        foreach ($sDevices as $device) {
            $animalKey = $sAnimalsByDevice[$device->device_id] ?? null;
            $animal = $animalKey ? collect($sAnimals)->firstWhere('animal_id', $animalKey) : null;
            $device->animal_id = $animal?->id;
            $device->owner_id = $animal?->owner_id ?? $springs->id;
            $device->save();
        }

        // Groups for Farm 2
        $sGroup1 = AnimalGroup::create(['name' => 'Desert Springs Mixed Herd', 'description' => 'Main herd of Desert Springs', 'color' => '#8B5CF6', 'owner_id' => $springs->id]);
        $sGroup1->animals()->sync([$sAnimals[0]->id, $sAnimals[1]->id]);
        $sGroup1->shepherds()->sync([$sShepherd->id]);

        $sGroup2 = AnimalGroup::create(['name' => 'Desert Camel Trainers', 'description' => 'Camels in light training', 'color' => '#EC4899', 'owner_id' => $springs->id]);
        $sGroup2->animals()->sync([$sAnimals[2]->id]);
        $sGroup2->shepherds()->sync([$sShepherd->id]);

        // Geofences for Farm 2
        $sGeos = [];
        $sGeoData = [
            ['name' => 'Springs South Range', 'coordinates' => json_encode([[25.1000, 47.2000], [25.1000, 47.2100], [25.0900, 47.2100], [25.0900, 47.2000], [25.1000, 47.2000]]), 'color' => '#06B6D4', 'alert_type' => 'exit', 'owner_id' => $springs->id],
            ['name' => 'Springs Watering Hole', 'coordinates' => json_encode([[25.1050, 47.2050], [25.1050, 47.2090], [25.1010, 47.2090], [25.1010, 47.2050], [25.1050, 47.2050]]), 'color' => '#22C55E', 'alert_type' => 'entry', 'owner_id' => $springs->id],
        ];
        foreach ($sGeoData as $g) {
            $sGeos[] = Geofence::create($g);
        }
        $sGeos[0]->animals()->sync([$sAnimals[0]->id, $sAnimals[1]->id, $sAnimals[2]->id]);
        $sGeos[0]->groups()->sync([$sGroup1->id]);
        $sGeos[1]->animals()->sync([$sAnimals[0]->id, $sAnimals[1]->id]);

        // Location history for Farm 2
        foreach ($sDevices as $device) {
            $animal = $device->animal;
            if (!$animal) continue;
            $lat = $device->gps_lat;
            $lng = $device->gps_lng;
            $heading = rand(0, 360);
            for ($i = 0; $i < 24; $i++) {
                $heading = ($heading + rand(-30, 30) + 360) % 360;
                $dist = rand(30, 150) / 10000;
                $rad = $heading * (M_PI / 180);
                $lat += $dist * cos($rad);
                $lng += $dist * sin($rad);
                LocationHistory::create([
                    'device_id' => $device->id, 'animal_id' => $animal->id,
                    'latitude' => $lat, 'longitude' => $lng,
                    'recorded_at' => now()->subHours($i * 2),
                    'speed' => rand(0, 60) / 10, 'heading' => $heading,
                ]);
            }
            $device->update(['gps_lat' => $lat, 'gps_lng' => $lng]);
        }

        // Medical and vaccinations for Farm 2
        MedicalRecord::create(['animal_id' => $sAnimals[0]->id, 'owner_id' => $springs->id, 'record_type' => 'checkup', 'title' => 'Reem health check', 'description' => 'Routine checkup. Mild vitamin deficiency noted.', 'record_date' => now()->subDays(7), 'veterinarian' => 'Dr. Huda Al-Tabib', 'status' => 'completed', 'health_status' => 'fair']);
        VaccinationSchedule::create(['animal_id' => $sAnimals[1]->id, 'owner_id' => $springs->id, 'vaccine_name' => 'Clostridial 5-in-1', 'scheduled_date' => now()->addDays(10), 'status' => 'scheduled']);

        // Tasks for Farm 2
        Task::create(['owner_id' => $springs->id, 'assigned_to' => $sShepherd->id, 'animal_id' => $sAnimals[0]->id, 'title' => 'Reem vitamin supplement', 'description' => 'Administer vitamin B complex', 'priority' => 'high', 'task_type' => 'medical', 'status' => 'pending', 'due_date' => now()->addHours(8)]);
        Task::create(['owner_id' => $springs->id, 'assigned_to' => $sShepherd->id, 'animal_id' => null, 'title' => 'South range fence check', 'description' => 'Inspect fence along southern boundary', 'priority' => 'medium', 'task_type' => 'inspection', 'status' => 'in_progress', 'due_date' => now()->addDay()]);
        Task::create(['owner_id' => $springs->id, 'assigned_to' => $sShepherd->id, 'animal_id' => $sAnimals[2]->id, 'title' => 'Nadim water refill', 'description' => 'Refill water trough in camel enclosure', 'priority' => 'low', 'task_type' => 'feeding', 'status' => 'pending', 'due_date' => now()->addHours(5)]);

        // Alert for Farm 2
        GeofenceAlert::create(['geofence_id' => $sGeos[0]->id, 'animal_id' => $sAnimals[0]->id, 'device_id' => $sDevices[0]->id, 'type' => 'exit', 'severity' => 'warning', 'message' => 'Reem (DSF-001) has exited Springs South Range', 'resolved' => false, 'is_acknowledged' => false, 'latitude' => 25.0990, 'longitude' => 47.1990, 'triggered_at' => now()->subHours(4)]);

        $this->command->info('  Farm 2: Desert Springs Farm (Starter) — 3 animals, 2 groups, 2 geofences, 1 staff');

        // ──────────────────────────────────────────────
        // Store Groq API key in settings (from env to avoid hardcoded secrets)
        // ──────────────────────────────────────────────
        $groqKey = env('GROQ_API_KEY', '');
        Setting::set('ai_provider', 'groq');
        if ($groqKey) {
            Setting::set('ai_api_key', $groqKey);
        }
        Setting::set('ai_model', 'llama-3.3-70b-versatile');
        $this->command->info('  Groq AI settings stored in database' . ($groqKey ? '' : ' (no API key in env)'));

        $this->command->info('=== FarmSeeder complete ===');
    }
}
