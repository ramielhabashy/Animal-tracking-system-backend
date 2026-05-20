<?php

namespace Database\Seeders;

use App\Models\{
    User, Animal, Device, AnimalGroup, Geofence,
    MedicalRecord, VaccinationSchedule, Task, PredefinedTask,
    Auction, Bid, Conversation, Message,
    OwnershipTransfer, OwnershipTransferAnimal, OwnershipHistory,
    UserSubscription, SubscriptionOrder, Banner, Setting, GeofenceAlert,
    TaskLog, Notification, AnimalDocument, Species, Breed,
    TaskType, MedicalRecordType, VaccinationType, LocationHistory
};
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('=== DemoDataSeeder: Resetting demo data ===');

        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        $tables = [
            'animals', 'devices', 'animal_groups', 'animal_group_member',
            'geofences', 'medical_records', 'vaccination_schedules',
            'tasks', 'predefined_tasks', 'auctions', 'bids',
            'conversations', 'conversation_user', 'messages', 'message_attachments',
            'ownership_transfers', 'ownership_transfer_animals', 'ownership_history',
            'user_subscriptions', 'subscription_orders', 'banners',
            'geofence_alerts', 'location_history', 'task_logs',
            'notifications', 'animal_documents', 'animal_geofence',
            'animal_group_geofence', 'group_shepherd',
        ];
        foreach ($tables as $table) {
            DB::table($table)->truncate();
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $this->command->info('Truncated all content tables.');

        // ──────────────────────────────────────────────
        // 1. SPECIES & BREEDS
        // ──────────────────────────────────────────────
        $speciesData = [
            ['name' => 'Camel', 'description' => 'Dromedary camel (Camelus dromedarius)', 'is_active' => true, 'breeds' => ['Majaheem', 'Wadhah', 'Suhail', 'Safari', 'Hisma']],
            ['name' => 'Goat', 'description' => 'Domestic goat (Capra hircus)', 'is_active' => true, 'breeds' => ['Boer', 'Damascus', 'Saanen', 'Nubian']],
            ['name' => 'Sheep', 'description' => 'Domestic sheep (Ovis aries)', 'is_active' => true, 'breeds' => ['Awassi', 'Najdi', 'Harri', 'Suffolk']],
            ['name' => 'Cattle', 'description' => 'Domestic cattle (Bos taurus)', 'is_active' => true, 'breeds' => ['Holstein', 'Angus', 'Hereford']],
            ['name' => 'Horse', 'description' => 'Domestic horse (Equus ferus caballus)', 'is_active' => true, 'breeds' => ['Arabian', 'Thoroughbred', 'Quarter Horse']],
        ];
        foreach ($speciesData as $sd) {
            $breedsList = $sd['breeds'];
            unset($sd['breeds']);
            $species = Species::updateOrCreate(['name' => $sd['name']], $sd);
            foreach ($breedsList as $breedName) {
                Breed::updateOrCreate(
                    ['species_id' => $species->id, 'name' => $breedName],
                    ['name' => $breedName, 'description' => "{$breedName} {$species->name}", 'is_active' => true]
                );
            }
        }
        $this->command->info('  ' . Species::count() . ' species + ' . Breed::count() . ' breeds');

        // ──────────────────────────────────────────────
        // 2. TAXONOMY TYPES
        // ──────────────────────────────────────────────
        $taskTypes = [
            ['name' => 'Feeding', 'slug' => 'feeding', 'icon' => 'restaurant', 'color' => '#22C55E'],
            ['name' => 'Medical', 'slug' => 'medical', 'icon' => 'local_hospital', 'color' => '#EF4444'],
            ['name' => 'Inspection', 'slug' => 'inspection', 'icon' => 'search', 'color' => '#3B82F6'],
            ['name' => 'Movement', 'slug' => 'movement', 'icon' => 'directions_walk', 'color' => '#F59E0B'],
            ['name' => 'Other', 'slug' => 'other', 'icon' => 'more_horiz', 'color' => '#6B7280'],
            ['name' => 'Vaccination', 'slug' => 'vaccination', 'icon' => 'vaccines', 'color' => '#A855F7'],
            ['name' => 'Breeding', 'slug' => 'breeding', 'icon' => 'pets', 'color' => '#EC4899'],
        ];
        foreach ($taskTypes as $tt) {
            TaskType::updateOrCreate(['slug' => $tt['slug']], $tt);
        }

        $medicalTypes = [
            ['name' => 'Checkup', 'slug' => 'checkup', 'icon' => 'monitor_heart', 'color' => '#22C55E'],
            ['name' => 'Treatment', 'slug' => 'treatment', 'icon' => 'medication', 'color' => '#3B82F6'],
            ['name' => 'Emergency', 'slug' => 'emergency', 'icon' => 'warning', 'color' => '#EF4444'],
            ['name' => 'Surgery', 'slug' => 'surgery', 'icon' => 'biotech', 'color' => '#A855F7'],
            ['name' => 'Dental', 'slug' => 'dental', 'icon' => 'tooth', 'color' => '#F59E0B'],
            ['name' => 'Laboratory', 'slug' => 'laboratory', 'icon' => 'science', 'color' => '#06B6D4'],
        ];
        foreach ($medicalTypes as $mt) {
            MedicalRecordType::updateOrCreate(['slug' => $mt['slug']], $mt);
        }

        $vaccinationTypes = [
            ['name' => 'Clostridial 5-in-1', 'slug' => 'clostridial-5-in-1', 'icon' => 'vaccines', 'color' => '#22C55E'],
            ['name' => 'Rabies', 'slug' => 'rabies', 'icon' => 'vaccines', 'color' => '#EF4444'],
            ['name' => 'CD-T', 'slug' => 'cd-t', 'icon' => 'vaccines', 'color' => '#3B82F6'],
            ['name' => 'BRSV', 'slug' => 'brsv', 'icon' => 'vaccines', 'color' => '#F59E0B'],
            ['name' => 'Anthrax', 'slug' => 'anthrax', 'icon' => 'vaccines', 'color' => '#A855F7'],
            ['name' => 'Foot & Mouth', 'slug' => 'foot-and-mouth', 'icon' => 'vaccines', 'color' => '#EC4899'],
        ];
        foreach ($vaccinationTypes as $vt) {
            VaccinationType::updateOrCreate(['slug' => $vt['slug']], $vt);
        }

        // ──────────────────────────────────────────────
        // 3. USERS
        // ──────────────────────────────────────────────
        $ownerRole = Role::where('name', 'Owner')->first();
        $shepherdRole = Role::where('name', 'Shepherd')->first();

        $freeTier = \App\Models\SubscriptionTier::where('slug', 'free')->first();
        $starterTier = \App\Models\SubscriptionTier::where('slug', 'starter')->first();

        $demo1 = User::updateOrCreate(
            ['email' => 'demo1@oasis.com'],
            ['name' => 'Ahmed Al-Demo', 'password' => Hash::make('password'), 'is_active' => true, 'phone' => '+966500000001']
        );
        $demo1->syncRoles([$ownerRole]);

        $demo2 = User::updateOrCreate(
            ['email' => 'demo2@oasis.com'],
            ['name' => 'Sara Al-Demo', 'password' => Hash::make('password'), 'is_active' => true, 'phone' => '+966500000002']
        );
        $demo2->syncRoles([$ownerRole]);

        $shepherd1 = User::updateOrCreate(
            ['email' => 'youssef@demo.com'],
            ['name' => 'Youssef Shepherd', 'password' => Hash::make('password'), 'is_active' => true, 'phone' => '+966500000003', 'managed_by' => $demo1->id]
        );
        $shepherd1->syncRoles([$shepherdRole]);

        $shepherd2 = User::updateOrCreate(
            ['email' => 'hassan@demo.com'],
            ['name' => 'Hassan Shepherd', 'password' => Hash::make('password'), 'is_active' => true, 'phone' => '+966500000004', 'managed_by' => $demo2->id]
        );
        $shepherd2->syncRoles([$shepherdRole]);

        $supportRole = Role::where('name', 'Support')->first();
        $accountantRole = Role::where('name', 'Accountant')->first();
        $csRole = Role::where('name', 'Customer Service')->first();

        $supportUser = User::updateOrCreate(
            ['email' => 'support@oasis.com'],
            ['name' => 'Khalid Support', 'password' => Hash::make('password'), 'is_active' => true, 'phone' => '+966500000005']
        );
        $supportUser->syncRoles([$supportRole]);

        $accountantUser = User::updateOrCreate(
            ['email' => 'accounts@oasis.com'],
            ['name' => 'Mona Accountant', 'password' => Hash::make('password'), 'is_active' => true, 'phone' => '+966500000006']
        );
        $accountantUser->syncRoles([$accountantRole]);

        $csUser = User::updateOrCreate(
            ['email' => 'cs@oasis.com'],
            ['name' => 'Nora Customer Service', 'password' => Hash::make('password'), 'is_active' => true, 'phone' => '+966500000007']
        );
        $csUser->syncRoles([$csRole]);

        $managerRole = Role::where('name', 'Manager')->first();
        $managerUser = User::updateOrCreate(
            ['email' => 'manager@demo.com'],
            ['name' => 'Faisal Manager', 'password' => Hash::make('password'), 'is_active' => true, 'phone' => '+966500000008']
        );
        $managerUser->syncRoles([$managerRole]);

        $doctorRole = Role::where('name', 'Doctor')->first();
        $doctorUser = User::updateOrCreate(
            ['email' => 'doctor@demo.com'],
            ['name' => 'Dr. Layla Vet', 'password' => Hash::make('password'), 'is_active' => true, 'phone' => '+966500000009']
        );
        $doctorUser->syncRoles([$doctorRole]);

        $demoUsers = [$demo1, $demo2];
        $demoShepherds = [1 => $shepherd1, 2 => $shepherd2];

        $adminUser = User::where('email', 'admin@oasis.com')->first();

        // ──────────────────────────────────────────────
        // 4. DEVICES
        // ──────────────────────────────────────────────
        $deviceData = [
            ['device_id' => 'DMO-0001', 'firmware_version' => 'v2.5.0', 'status' => 'online', 'battery_level' => 92, 'gps_lat' => 24.7136, 'gps_lng' => 46.6753, 'last_ping' => now()->subMinutes(3), 'temperature' => 38.6],
            ['device_id' => 'DMO-0002', 'firmware_version' => 'v2.5.0', 'status' => 'online', 'battery_level' => 88, 'gps_lat' => 24.7140, 'gps_lng' => 46.6760, 'last_ping' => now()->subMinutes(1), 'temperature' => 38.3],
            ['device_id' => 'DMO-0003', 'firmware_version' => 'v2.4.1', 'status' => 'online', 'battery_level' => 76, 'gps_lat' => 24.7145, 'gps_lng' => 46.6765, 'last_ping' => now()->subMinutes(7), 'temperature' => 38.9],
            ['device_id' => 'DMO-0004', 'firmware_version' => 'v2.5.0', 'status' => 'online', 'battery_level' => 95, 'gps_lat' => 24.7150, 'gps_lng' => 46.6770, 'last_ping' => now()->subMinutes(2), 'temperature' => 38.2],
            ['device_id' => 'DMO-0005', 'firmware_version' => 'v2.4.1', 'status' => 'low_signal', 'battery_level' => 23, 'gps_lat' => 24.7155, 'gps_lng' => 46.6775, 'last_ping' => now()->subMinutes(20), 'temperature' => 39.6],
            ['device_id' => 'DMO-0006', 'firmware_version' => 'v2.5.0', 'status' => 'online', 'battery_level' => 74, 'gps_lat' => 24.7120, 'gps_lng' => 46.6740, 'last_ping' => now()->subMinutes(5), 'temperature' => 38.7],
            ['device_id' => 'DMO-0007', 'firmware_version' => 'v2.4.0', 'status' => 'offline', 'battery_level' => 5, 'gps_lat' => 24.7160, 'gps_lng' => 46.6780, 'last_ping' => now()->subHours(4), 'temperature' => 37.8],
            ['device_id' => 'DMO-0008', 'firmware_version' => 'v2.5.0', 'status' => 'online', 'battery_level' => 81, 'gps_lat' => 24.7090, 'gps_lng' => 46.6720, 'last_ping' => now()->subMinutes(8), 'temperature' => 39.1],
            ['device_id' => 'DMO-0009', 'firmware_version' => 'v2.5.0', 'status' => 'online', 'battery_level' => 67, 'gps_lat' => 24.7170, 'gps_lng' => 46.6790, 'last_ping' => now()->subMinutes(12), 'temperature' => 38.4],
            ['device_id' => 'DMO-0010', 'firmware_version' => 'v2.4.1', 'status' => 'online', 'battery_level' => 90, 'gps_lat' => 24.7110, 'gps_lng' => 46.6730, 'last_ping' => now()->subMinutes(6), 'temperature' => 38.1],
        ];
        foreach ($deviceData as $d) {
            Device::create($d);
        }
        $allDevices = Device::whereIn('device_id', array_column($deviceData, 'device_id'))->get()->keyBy('device_id');

        // ──────────────────────────────────────────────
        // 5. ANIMALS (with names)
        // ──────────────────────────────────────────────
        $animalData = [
            ['animal_id' => 'DMO-001', 'name' => 'Sultan', 'species' => 'Camel', 'breed' => 'Majaheem', 'gender' => 'Male', 'date_of_birth' => '2021-05-10', 'color_markings' => 'White with brown patches', 'current_weight' => 680, 'baseline_temperature' => 38.5, 'owner_id' => $demo1->id],
            ['animal_id' => 'DMO-002', 'name' => 'Noora', 'species' => 'Camel', 'breed' => 'Wadhah', 'gender' => 'Female', 'date_of_birth' => '2022-08-15', 'color_markings' => 'Golden brown', 'current_weight' => 590, 'baseline_temperature' => 38.2, 'owner_id' => $demo1->id],
            ['animal_id' => 'DMO-003', 'name' => 'AsiL', 'species' => 'Camel', 'breed' => 'Suhail', 'gender' => 'Male', 'date_of_birth' => '2023-02-20', 'color_markings' => 'Dark brown, white legs', 'current_weight' => 520, 'baseline_temperature' => 38.8, 'owner_id' => $demo1->id],
            ['animal_id' => 'DMO-004', 'name' => 'Lulu', 'species' => 'Camel', 'breed' => 'Majaheem', 'gender' => 'Female', 'date_of_birth' => '2021-11-05', 'color_markings' => 'Cream colored', 'current_weight' => 620, 'baseline_temperature' => 38.4, 'owner_id' => $demo2->id],
            ['animal_id' => 'DMO-005', 'name' => 'Zafir', 'species' => 'Goat', 'breed' => 'Boer', 'gender' => 'Male', 'date_of_birth' => '2024-01-10', 'color_markings' => 'White with brown head', 'current_weight' => 88, 'baseline_temperature' => 39.5, 'owner_id' => $demo2->id],
            ['animal_id' => 'DMO-006', 'name' => 'Barq', 'species' => 'Goat', 'breed' => 'Damascus', 'gender' => 'Male', 'date_of_birth' => '2023-06-15', 'color_markings' => 'Reddish brown with long ears', 'current_weight' => 95, 'baseline_temperature' => 39.3, 'owner_id' => $demo1->id],
            ['animal_id' => 'DMO-007', 'name' => 'Sahm', 'species' => 'Sheep', 'breed' => 'Awassi', 'gender' => 'Male', 'date_of_birth' => '2023-04-18', 'color_markings' => 'White wool, black face', 'current_weight' => 92, 'baseline_temperature' => 39.8, 'owner_id' => $demo2->id],
            ['animal_id' => 'DMO-008', 'name' => 'Rayyan', 'species' => 'Sheep', 'breed' => 'Najdi', 'gender' => 'Female', 'date_of_birth' => '2022-11-20', 'color_markings' => 'Cream colored wool', 'current_weight' => 78, 'baseline_temperature' => 39.4, 'owner_id' => $demo1->id],
            ['animal_id' => 'DMO-009', 'name' => 'Bashir', 'species' => 'Cattle', 'breed' => 'Holstein', 'gender' => 'Female', 'date_of_birth' => '2021-09-10', 'color_markings' => 'Black and white patches', 'current_weight' => 620, 'baseline_temperature' => 38.9, 'owner_id' => $demo2->id],
            ['animal_id' => 'DMO-010', 'name' => 'Jood', 'species' => 'Horse', 'breed' => 'Arabian', 'gender' => 'Female', 'date_of_birth' => '2020-03-25', 'color_markings' => 'Pure white', 'current_weight' => 420, 'baseline_temperature' => 37.8, 'owner_id' => $demo1->id],
        ];
        foreach ($animalData as $a) {
            $existing = Animal::where('animal_id', $a['animal_id'])->first();
            if ($existing) {
                $existing->update($a);
            } else {
                Animal::create($a);
            }
        }
        $allAnimals = Animal::whereIn('animal_id', array_column($animalData, 'animal_id'))->get()->keyBy('animal_id');

        $deviceAnimalMap = [
            'DMO-0001' => 'DMO-001', 'DMO-0002' => 'DMO-002', 'DMO-0003' => 'DMO-003',
            'DMO-0004' => 'DMO-004', 'DMO-0005' => 'DMO-005', 'DMO-0006' => 'DMO-006',
            'DMO-0007' => 'DMO-007', 'DMO-0008' => 'DMO-008', 'DMO-0009' => 'DMO-009',
            'DMO-0010' => 'DMO-010',
        ];
        foreach ($allDevices as $device) {
            $animalIdKey = $deviceAnimalMap[$device->device_id] ?? null;
            $animal = $animalIdKey ? $allAnimals->get($animalIdKey) : null;
            $device->animal_id = $animal?->id;
            $device->owner_id = $animal?->owner_id ?? $demo1->id;
            $device->save();
        }

        // ──────────────────────────────────────────────
        // 6. ANIMAL GROUPS
        // ──────────────────────────────────────────────
        $group1 = AnimalGroup::create([
            'name' => 'Ahmed Premium Herd',
            'description' => 'Top breeding camels from Ahmed Al-Demo',
            'color' => '#10b981',
            'owner_id' => $demo1->id,
        ]);
        $group1->animals()->sync([
            $allAnimals->get('DMO-001')->id,
            $allAnimals->get('DMO-002')->id,
        ]);
        $group1->shepherds()->sync([$shepherd1->id]);

        $group2 = AnimalGroup::create([
            'name' => 'Young Racers & Goats',
            'description' => 'Young racing camels and goats in training',
            'color' => '#3b82f6',
            'owner_id' => $demo1->id,
        ]);
        $group2->animals()->sync([
            $allAnimals->get('DMO-003')->id,
            $allAnimals->get('DMO-006')->id,
            $allAnimals->get('DMO-008')->id,
        ]);
        $group2->shepherds()->sync([$shepherd1->id]);

        $group3 = AnimalGroup::create([
            'name' => 'Sara Mixed Herd',
            'description' => 'Sara Al-Demo animals',
            'color' => '#f59e0b',
            'owner_id' => $demo2->id,
        ]);
        $group3->animals()->sync([
            $allAnimals->get('DMO-004')->id,
            $allAnimals->get('DMO-005')->id,
            $allAnimals->get('DMO-007')->id,
            $allAnimals->get('DMO-009')->id,
        ]);
        $group3->shepherds()->sync([$shepherd2->id]);

        AnimalGroup::create([
            'name' => 'Equestrian Division',
            'description' => 'Horses and riding animals',
            'color' => '#ec4899',
            'owner_id' => $demo1->id,
        ])->animals()->sync([$allAnimals->get('DMO-010')->id]);

        // ──────────────────────────────────────────────
        // 7. GEOFENCES
        // ──────────────────────────────────────────────
        $geofences = [
            ['name' => 'Ahmed North Paddock', 'coordinates' => json_encode([[24.7136, 46.6753], [24.7136, 46.6853], [24.7036, 46.6853], [24.7036, 46.6753], [24.7136, 46.6753]]), 'color' => '#22C55E', 'alert_type' => 'both', 'is_active' => true, 'owner_id' => $demo1->id],
            ['name' => 'Sara South Paddock', 'coordinates' => json_encode([[24.7200, 46.6800], [24.7200, 46.6900], [24.7100, 46.6900], [24.7100, 46.6800], [24.7200, 46.6800]]), 'color' => '#3B82F6', 'alert_type' => 'exit', 'is_active' => true, 'owner_id' => $demo2->id],
            ['name' => 'Racing Track', 'coordinates' => json_encode([[24.7250, 46.6700], [24.7250, 46.6780], [24.7000, 46.6780], [24.7000, 46.6700], [24.7250, 46.6700]]), 'color' => '#F59E0B', 'alert_type' => 'entry', 'is_active' => true, 'owner_id' => $demo1->id],
            ['name' => 'Quarantine Zone', 'coordinates' => json_encode([[24.7300, 46.6650], [24.7300, 46.6720], [24.7220, 46.6720], [24.7220, 46.6650], [24.7300, 46.6650]]), 'color' => '#EF4444', 'alert_type' => 'both', 'is_active' => true, 'owner_id' => $demo2->id],
            ['name' => 'Watering Point', 'coordinates' => json_encode([[24.7180, 46.6820], [24.7180, 46.6860], [24.7140, 46.6860], [24.7140, 46.6820], [24.7180, 46.6820]]), 'color' => '#06B6D4', 'alert_type' => 'entry', 'is_active' => true, 'owner_id' => $demo1->id],
        ];
        foreach ($geofences as $g) {
            Geofence::create($g);
        }
        $allGeofences = Geofence::all();

        // Assign animals to geofences
        $allGeofences[0]->animals()->sync([$allAnimals->get('DMO-001')->id, $allAnimals->get('DMO-002')->id, $allAnimals->get('DMO-003')->id]);
        $allGeofences[1]->animals()->sync([$allAnimals->get('DMO-004')->id, $allAnimals->get('DMO-005')->id]);
        $allGeofences[2]->animals()->sync([$allAnimals->get('DMO-003')->id]);
        $allGeofences[4]->animals()->sync([$allAnimals->get('DMO-001')->id, $allAnimals->get('DMO-002')->id, $allAnimals->get('DMO-006')->id]);

        // Assign groups to geofences
        $allGeofences[0]->groups()->sync([$group1->id]);
        $allGeofences[1]->groups()->sync([$group3->id]);

        // ──────────────────────────────────────────────
        // 8. GEOFENCE ALERTS
        // ──────────────────────────────────────────────
        $alertData = [
            ['geofence_id' => $allGeofences[0]->id, 'animal_id' => $allAnimals->get('DMO-001')->id, 'device_id' => $allDevices->get('DMO-0001')->id, 'type' => 'exit', 'severity' => 'warning', 'message' => 'Sultan (DMO-001) has exited Ahmed North Paddock', 'resolved' => true, 'is_acknowledged' => true, 'latitude' => 24.7120, 'longitude' => 46.6740, 'triggered_at' => now()->subDays(3)],
            ['geofence_id' => $allGeofences[0]->id, 'animal_id' => $allAnimals->get('DMO-002')->id, 'device_id' => $allDevices->get('DMO-0002')->id, 'type' => 'exit', 'severity' => 'critical', 'message' => 'Noora (DMO-002) has exited Ahmed North Paddock - immediate attention required', 'resolved' => false, 'is_acknowledged' => false, 'latitude' => 24.7110, 'longitude' => 46.6730, 'triggered_at' => now()->subHours(6)],
            ['geofence_id' => $allGeofences[1]->id, 'animal_id' => $allAnimals->get('DMO-005')->id, 'device_id' => $allDevices->get('DMO-0005')->id, 'type' => 'exit', 'severity' => 'warning', 'message' => 'Zafir (DMO-005) has exited Sara South Paddock', 'resolved' => true, 'is_acknowledged' => true, 'latitude' => 24.7140, 'longitude' => 46.6760, 'triggered_at' => now()->subDays(1)],
            ['geofence_id' => $allGeofences[1]->id, 'animal_id' => $allAnimals->get('DMO-004')->id, 'device_id' => $allDevices->get('DMO-0004')->id, 'type' => 'exit', 'severity' => 'info', 'message' => 'Lulu (DMO-004) approached paddock boundary', 'resolved' => false, 'is_acknowledged' => false, 'latitude' => 24.7160, 'longitude' => 46.6780, 'triggered_at' => now()->subHours(2)],
            ['geofence_id' => $allGeofences[2]->id, 'animal_id' => $allAnimals->get('DMO-003')->id, 'device_id' => $allDevices->get('DMO-0003')->id, 'type' => 'entry', 'severity' => 'info', 'message' => 'AsiL (DMO-003) entered racing track area', 'resolved' => true, 'is_acknowledged' => true, 'latitude' => 24.7145, 'longitude' => 46.6765, 'triggered_at' => now()->subDays(2)],
            ['geofence_id' => $allGeofences[3]->id, 'animal_id' => $allAnimals->get('DMO-009')->id, 'device_id' => $allDevices->get('DMO-0009')->id, 'type' => 'entry', 'severity' => 'critical', 'message' => 'Bashir (DMO-009) entered Quarantine Zone - possible health issue', 'resolved' => false, 'is_acknowledged' => false, 'latitude' => 24.7170, 'longitude' => 46.6790, 'triggered_at' => now()->subHour()],
        ];
        foreach ($alertData as $al) {
            GeofenceAlert::create($al);
        }

        // Temperature alerts (type='temperature', no geofence)
        $tempAlertData = [
            ['geofence_id' => null, 'animal_id' => $allAnimals->get('DMO-001')->id, 'device_id' => $allDevices->get('DMO-0001')->id, 'type' => 'temperature', 'severity' => 'warning', 'message' => 'Sultan (DMO-001) temperature elevated to 39.8°C', 'resolved' => false, 'is_acknowledged' => false, 'latitude' => 24.7136, 'longitude' => 46.6753, 'triggered_at' => now()->subHours(3)],
            ['geofence_id' => null, 'animal_id' => $allAnimals->get('DMO-005')->id, 'device_id' => $allDevices->get('DMO-0005')->id, 'type' => 'temperature', 'severity' => 'critical', 'message' => 'Zafir (DMO-005) temperature critically high at 40.2°C - immediate attention required', 'resolved' => false, 'is_acknowledged' => false, 'latitude' => 24.7155, 'longitude' => 46.6775, 'triggered_at' => now()->subHour()],
            ['geofence_id' => null, 'animal_id' => $allAnimals->get('DMO-007')->id, 'device_id' => $allDevices->get('DMO-0007')->id, 'type' => 'temperature', 'severity' => 'info', 'message' => 'Sahm (DMO-007) temperature returned to normal at 38.9°C', 'resolved' => true, 'is_acknowledged' => true, 'latitude' => 24.7160, 'longitude' => 46.6780, 'triggered_at' => now()->subDays(1)],
            ['geofence_id' => null, 'animal_id' => $allAnimals->get('DMO-009')->id, 'device_id' => $allDevices->get('DMO-0009')->id, 'type' => 'temperature', 'severity' => 'warning', 'message' => 'Bashir (DMO-009) temperature elevated to 39.6°C post-calving', 'resolved' => false, 'is_acknowledged' => false, 'latitude' => 24.7170, 'longitude' => 46.6790, 'triggered_at' => now()->subHours(5)],
        ];
        foreach ($tempAlertData as $tal) {
            GeofenceAlert::create($tal);
        }

        // ──────────────────────────────────────────────
        // 9. ANIMAL DOCUMENTS
        // ──────────────────────────────────────────────
        $docData = [
            ['animal_id' => $allAnimals->get('DMO-001')->id, 'type' => 'registration', 'file_path' => 'documents/sultan_registration.pdf', 'original_name' => 'sultan_registration.pdf', 'mime_type' => 'application/pdf', 'file_size' => 245000, 'notes' => 'Official registration certificate for Sultan'],
            ['animal_id' => $allAnimals->get('DMO-001')->id, 'type' => 'health_certificate', 'file_path' => 'documents/sultan_health_cert.pdf', 'original_name' => 'sultan_health_cert.pdf', 'mime_type' => 'application/pdf', 'file_size' => 180000, 'notes' => 'Annual health certificate issued by Dr. Fatima'],
            ['animal_id' => $allAnimals->get('DMO-003')->id, 'type' => 'pedigree', 'file_path' => 'documents/asil_pedigree.pdf', 'original_name' => 'asil_pedigree.pdf', 'mime_type' => 'application/pdf', 'file_size' => 320000, 'notes' => 'Pedigree certificate showing racing lineage'],
            ['animal_id' => $allAnimals->get('DMO-004')->id, 'type' => 'vaccination_record', 'file_path' => 'documents/lulu_vaccinations.pdf', 'original_name' => 'lulu_vaccinations.pdf', 'mime_type' => 'application/pdf', 'file_size' => 95000, 'notes' => 'Complete vaccination history'],
            ['animal_id' => $allAnimals->get('DMO-010')->id, 'type' => 'registration', 'file_path' => 'documents/jood_purebred_cert.pdf', 'original_name' => 'jood_purebred_cert.pdf', 'mime_type' => 'application/pdf', 'file_size' => 410000, 'notes' => 'Purebred Arabian horse certificate'],
        ];
        foreach ($docData as $doc) {
            AnimalDocument::create($doc);
        }

        // ──────────────────────────────────────────────
        // 10. LOCATION HISTORY
        // ──────────────────────────────────────────────
        foreach ($allDevices->whereNotNull('gps_lat') as $device) {
            $animal = $device->animal;
            if (!$animal) continue;

            $baseLat = $device->gps_lat;
            $baseLng = $device->gps_lng;
            $currentLat = $baseLat;
            $currentLng = $baseLng;
            $currentHeading = rand(0, 360);

            for ($i = 0; $i < 48; $i++) {
                $headingChange = rand(-30, 30);
                $currentHeading = ($currentHeading + $headingChange + 360) % 360;
                $distance = rand(50, 200) / 10000;
                $radians = $currentHeading * (M_PI / 180);
                $newLat = $currentLat + ($distance * cos($radians));
                $newLng = $currentLng + ($distance * sin($radians));
                $speed = rand(0, 80) / 10;

                LocationHistory::create([
                    'device_id' => $device->id,
                    'animal_id' => $animal->id,
                    'latitude' => $newLat,
                    'longitude' => $newLng,
                    'recorded_at' => now()->subHours($i * 2),
                    'speed' => $speed,
                    'heading' => $currentHeading,
                ]);
                $currentLat = $newLat;
                $currentLng = $newLng;
            }

            $device->update([
                'gps_lat' => $currentLat,
                'gps_lng' => $currentLng,
            ]);
        }

        // ──────────────────────────────────────────────
        // 11. MEDICAL RECORDS
        // ──────────────────────────────────────────────
        $animal1 = $allAnimals->get('DMO-001');
        $animal2 = $allAnimals->get('DMO-002');
        $animal3 = $allAnimals->get('DMO-003');
        $animal4 = $allAnimals->get('DMO-004');
        $animal5 = $allAnimals->get('DMO-005');
        $animal6 = $allAnimals->get('DMO-006');
        $animal7 = $allAnimals->get('DMO-007');
        $animal8 = $allAnimals->get('DMO-008');
        $animal9 = $allAnimals->get('DMO-009');
        $animal10 = $allAnimals->get('DMO-010');

        $medicals = [
            ['animal_id' => $animal1->id, 'owner_id' => $demo1->id, 'record_type' => 'checkup', 'title' => 'Routine health check - Sultan', 'description' => 'Annual health assessment. All vitals normal.', 'record_date' => now()->subDays(45), 'veterinarian' => 'Dr. Fatima Al-Said', 'status' => 'completed', 'health_status' => 'good'],
            ['animal_id' => $animal1->id, 'owner_id' => $demo1->id, 'record_type' => 'treatment', 'title' => 'Hoof trimming - Sultan', 'description' => 'Routine hoof maintenance.', 'record_date' => now()->subDays(20), 'veterinarian' => 'Dr. Fatima Al-Said', 'status' => 'completed'],
            ['animal_id' => $animal2->id, 'owner_id' => $demo1->id, 'record_type' => 'checkup', 'title' => 'Pre-breeding check - Noora', 'description' => 'Health evaluation before breeding season.', 'record_date' => now()->subDays(30), 'veterinarian' => 'Dr. Fatima Al-Said', 'status' => 'completed'],
            ['animal_id' => $animal3->id, 'owner_id' => $demo1->id, 'record_type' => 'checkup', 'title' => 'Racing fitness assessment - AsiL', 'description' => 'Cardiovascular and respiratory check.', 'record_date' => now()->subDays(15), 'veterinarian' => 'Dr. Fatima Al-Said', 'status' => 'completed', 'health_status' => 'excellent'],
            ['animal_id' => $animal4->id, 'owner_id' => $demo2->id, 'record_type' => 'checkup', 'title' => 'Routine health check - Lulu', 'description' => 'Annual checkup. All vitals normal.', 'record_date' => now()->subDays(60), 'veterinarian' => 'Dr. Fatima Al-Said', 'status' => 'completed'],
            ['animal_id' => $animal4->id, 'owner_id' => $demo2->id, 'record_type' => 'treatment', 'title' => 'Deworming - Lulu', 'description' => 'Routine deworming treatment.', 'record_date' => now()->subDays(10), 'status' => 'completed', 'medication' => 'Ivermectin', 'dosage' => '10ml'],
            ['animal_id' => $animal5->id, 'owner_id' => $demo2->id, 'record_type' => 'emergency', 'title' => 'Minor injury treatment - Zafir', 'description' => 'Small cut on leg treated and bandaged.', 'record_date' => now()->subDays(5), 'status' => 'completed', 'notes' => 'Healing well, bandage change in 3 days.'],
            ['animal_id' => $animal6->id, 'owner_id' => $demo1->id, 'record_type' => 'checkup', 'title' => 'New arrival checkup - Barq', 'description' => 'Initial health assessment for newly acquired goat.', 'record_date' => now()->subDays(14), 'veterinarian' => 'Dr. Layla Vet', 'status' => 'completed', 'health_status' => 'good'],
            ['animal_id' => $animal7->id, 'owner_id' => $demo2->id, 'record_type' => 'treatment', 'title' => 'Hoof rot treatment - Sahm', 'description' => 'Antibiotic treatment for early-stage hoof rot.', 'record_date' => now()->subDays(7), 'veterinarian' => 'Dr. Layla Vet', 'status' => 'in_progress', 'medication' => 'Oxytetracycline'],
            ['animal_id' => $animal9->id, 'owner_id' => $demo2->id, 'record_type' => 'checkup', 'title' => 'Post-calving check - Bashir', 'description' => 'Health check after recent calving.', 'record_date' => now()->subDays(3), 'veterinarian' => 'Dr. Fatima Al-Said', 'status' => 'completed', 'health_status' => 'good'],
            ['animal_id' => $animal10->id, 'owner_id' => $demo1->id, 'record_type' => 'checkup', 'title' => 'Annual veterinary assessment - Jood', 'description' => 'Complete physical exam and dental check.', 'record_date' => now()->subDays(25), 'veterinarian' => 'Dr. Layla Vet', 'status' => 'completed', 'health_status' => 'excellent'],
            ['animal_id' => $animal8->id, 'owner_id' => $demo1->id, 'record_type' => 'vaccination', 'title' => 'Annual booster - Rayyan', 'description' => 'Annual booster vaccination.', 'record_date' => now()->subDays(2), 'status' => 'completed'],
        ];
        foreach ($medicals as $m) {
            MedicalRecord::create($m);
        }

        // ──────────────────────────────────────────────
        // 12. VACCINATION SCHEDULES
        // ──────────────────────────────────────────────
        $vaccinations = [
            ['animal_id' => $animal1->id, 'owner_id' => $demo1->id, 'vaccine_name' => 'Clostridial 5-in-1', 'scheduled_date' => now()->subDays(90), 'administered_date' => now()->subDays(90), 'status' => 'administered', 'veterinarian' => 'Dr. Fatima Al-Said', 'dose_number' => 1, 'total_doses' => 1],
            ['animal_id' => $animal2->id, 'owner_id' => $demo1->id, 'vaccine_name' => 'Clostridial 5-in-1', 'scheduled_date' => now()->subDays(90), 'administered_date' => now()->subDays(90), 'status' => 'administered', 'veterinarian' => 'Dr. Fatima Al-Said', 'dose_number' => 1, 'total_doses' => 1],
            ['animal_id' => $animal2->id, 'owner_id' => $demo1->id, 'vaccine_name' => 'Rabies', 'scheduled_date' => now()->subDays(30), 'administered_date' => now()->subDays(30), 'status' => 'administered', 'veterinarian' => 'Dr. Fatima Al-Said'],
            ['animal_id' => $animal3->id, 'owner_id' => $demo1->id, 'vaccine_name' => 'Clostridial 5-in-1', 'scheduled_date' => now()->addDays(60), 'status' => 'scheduled'],
            ['animal_id' => $animal4->id, 'owner_id' => $demo2->id, 'vaccine_name' => 'Clostridial 5-in-1', 'scheduled_date' => now()->subDays(45), 'administered_date' => now()->subDays(45), 'status' => 'administered', 'veterinarian' => 'Dr. Fatima Al-Said'],
            ['animal_id' => $animal5->id, 'owner_id' => $demo2->id, 'vaccine_name' => 'CD-T', 'scheduled_date' => now()->addDays(15), 'status' => 'scheduled'],
            ['animal_id' => $animal6->id, 'owner_id' => $demo1->id, 'vaccine_name' => 'CD-T', 'scheduled_date' => now()->subDays(20), 'administered_date' => now()->subDays(20), 'status' => 'administered', 'veterinarian' => 'Dr. Layla Vet'],
            ['animal_id' => $animal7->id, 'owner_id' => $demo2->id, 'vaccine_name' => 'Clostridial 5-in-1', 'scheduled_date' => now()->addDays(30), 'status' => 'scheduled'],
            ['animal_id' => $animal9->id, 'owner_id' => $demo2->id, 'vaccine_name' => 'Foot & Mouth', 'scheduled_date' => now()->subDays(180), 'administered_date' => now()->subDays(180), 'status' => 'administered', 'veterinarian' => 'Dr. Fatima Al-Said', 'dose_number' => 1, 'total_doses' => 2],
            ['animal_id' => $animal10->id, 'owner_id' => $demo1->id, 'vaccine_name' => 'Rabies', 'scheduled_date' => now()->subDays(60), 'administered_date' => now()->subDays(60), 'status' => 'administered', 'veterinarian' => 'Dr. Layla Vet'],
            ['animal_id' => $animal8->id, 'owner_id' => $demo1->id, 'vaccine_name' => 'Clostridial 5-in-1', 'scheduled_date' => now()->addDays(45), 'status' => 'scheduled'],
            ['animal_id' => $animal3->id, 'owner_id' => $demo1->id, 'vaccine_name' => 'Rabies', 'scheduled_date' => now()->subDays(15), 'administered_date' => now()->subDays(15), 'status' => 'administered', 'veterinarian' => 'Dr. Fatima Al-Said'],
        ];
        foreach ($vaccinations as $v) {
            VaccinationSchedule::create($v);
        }

        // ──────────────────────────────────────────────
        // 13. TASKS
        // ──────────────────────────────────────────────
        $tasks = [
            ['owner_id' => $demo1->id, 'assigned_to' => $shepherd1->id, 'animal_id' => $animal1->id, 'title' => 'Morning feeding - Sultan', 'description' => 'Feed concentrate mix to Sultan (DMO-001)', 'priority' => 'high', 'task_type' => 'feeding', 'status' => 'in_progress', 'due_date' => now()->addHours(2)],
            ['owner_id' => $demo1->id, 'assigned_to' => $shepherd1->id, 'animal_id' => null, 'title' => 'Inspect north paddock fence', 'description' => 'Check for damage after last storm', 'priority' => 'medium', 'task_type' => 'inspection', 'status' => 'pending', 'due_date' => now()->addDays(1)],
            ['owner_id' => $demo1->id, 'assigned_to' => $shepherd1->id, 'animal_id' => $animal3->id, 'title' => 'AsiL temperature check', 'description' => 'Monitor temperature after training session', 'priority' => 'high', 'task_type' => 'medical', 'status' => 'pending', 'due_date' => now()->addHours(4)],
            ['owner_id' => $demo2->id, 'assigned_to' => $shepherd2->id, 'animal_id' => $animal5->id, 'title' => 'Bandage change - Zafir', 'description' => 'Change leg bandage and check healing', 'priority' => 'high', 'task_type' => 'medical', 'status' => 'pending', 'due_date' => now()->addDays(2)],
            ['owner_id' => $demo2->id, 'assigned_to' => $shepherd2->id, 'animal_id' => $animal4->id, 'title' => 'Water trough refill', 'description' => 'Refill water troughs in south paddock', 'priority' => 'low', 'task_type' => 'feeding', 'status' => 'completed', 'due_date' => now()->subDays(1), 'completed_at' => now()->subDays(1)],
            ['owner_id' => $demo1->id, 'assigned_to' => $shepherd1->id, 'animal_id' => $animal2->id, 'title' => 'Noora breeding preparation', 'description' => 'Prepare breeding area and document readiness', 'priority' => 'high', 'task_type' => 'breeding', 'status' => 'pending', 'due_date' => now()->addDays(3)],
            ['owner_id' => $demo1->id, 'assigned_to' => $shepherd1->id, 'animal_id' => null, 'title' => 'Quarterly report review', 'description' => 'Review herd health and growth metrics for Q2', 'priority' => 'low', 'task_type' => 'other', 'status' => 'completed', 'due_date' => now()->subDays(5), 'completed_at' => now()->subDays(5)],
            ['owner_id' => $demo1->id, 'assigned_to' => $shepherd1->id, 'animal_id' => $animal1->id, 'title' => 'Sultan hoof treatment follow-up', 'description' => 'Check hooves after recent trimming', 'priority' => 'medium', 'task_type' => 'medical', 'status' => 'in_progress', 'due_date' => now()->addHours(6)],
            ['owner_id' => $demo2->id, 'assigned_to' => $shepherd2->id, 'animal_id' => $animal4->id, 'title' => 'Lulu vaccination', 'description' => 'Administer annual booster vaccination', 'priority' => 'urgent', 'task_type' => 'vaccination', 'status' => 'pending', 'due_date' => now()->addDays(1)],
            ['owner_id' => $demo2->id, 'assigned_to' => $shepherd2->id, 'animal_id' => null, 'title' => 'South paddock rotation', 'description' => 'Move herd to fresh grazing area', 'priority' => 'medium', 'task_type' => 'movement', 'status' => 'in_progress', 'due_date' => now()->addHours(8)],
            ['owner_id' => $demo2->id, 'assigned_to' => $shepherd2->id, 'animal_id' => $animal4->id, 'title' => 'Milk production log - Lulu', 'description' => 'Record daily milk yield', 'priority' => 'low', 'task_type' => 'feeding', 'status' => 'completed', 'due_date' => now()->subDays(1), 'completed_at' => now()->subDays(1)],
            ['owner_id' => $demo1->id, 'assigned_to' => $shepherd1->id, 'animal_id' => $animal10->id, 'title' => 'Jood grooming session', 'description' => 'Full grooming and hoof check for Arabian horse', 'priority' => 'medium', 'task_type' => 'other', 'status' => 'pending', 'due_date' => now()->addDays(5)],
            ['owner_id' => $demo1->id, 'assigned_to' => $shepherd1->id, 'animal_id' => $animal6->id, 'title' => 'Barq deworming', 'description' => 'Administer deworming medication', 'priority' => 'high', 'task_type' => 'medical', 'status' => 'pending', 'due_date' => now()->addDays(3)],
            ['owner_id' => $demo2->id, 'assigned_to' => $shepherd2->id, 'animal_id' => $animal7->id, 'title' => 'Sahm hoof treatment', 'description' => 'Continue hoof rot treatment and re-bandage', 'priority' => 'urgent', 'task_type' => 'medical', 'status' => 'in_progress', 'due_date' => now()->addHours(12)],
            ['owner_id' => $demo2->id, 'assigned_to' => $shepherd2->id, 'animal_id' => $animal9->id, 'title' => 'Bashir milking schedule', 'description' => 'Morning and evening milking routine', 'priority' => 'high', 'task_type' => 'feeding', 'status' => 'delivered', 'due_date' => now()->subHours(6), 'delivered_at' => now()->subHours(8), 'delivered_by' => $shepherd2->id],
        ];
        foreach ($tasks as $t) {
            Task::create($t);
        }

        $predefinedTasks = [
            ['owner_id' => $demo1->id, 'title' => 'Morning water check', 'description' => 'Ensure all water troughs are filled and clean', 'priority' => 'high', 'task_type' => 'feeding', 'is_recurring' => true, 'recurrence_type' => 'daily', 'recurrence_interval' => 1],
            ['owner_id' => $demo1->id, 'title' => 'Evening feeding', 'description' => 'Distribute feed supplements and check food supply', 'priority' => 'medium', 'task_type' => 'feeding', 'is_recurring' => true, 'recurrence_type' => 'daily', 'recurrence_interval' => 1],
            ['owner_id' => $demo1->id, 'title' => 'Weekly health inspection', 'description' => 'Check all animals for signs of illness or injury', 'priority' => 'high', 'task_type' => 'medical', 'is_recurring' => true, 'recurrence_type' => 'weekly', 'recurrence_interval' => 1],
            ['owner_id' => $demo2->id, 'title' => 'Device battery check', 'description' => 'Verify GPS tracking devices have adequate battery', 'priority' => 'medium', 'task_type' => 'inspection', 'is_recurring' => true, 'recurrence_type' => 'weekly', 'recurrence_interval' => 1],
            ['owner_id' => $demo2->id, 'title' => 'Fence integrity check', 'description' => 'Inspect perimeter fencing for damage', 'priority' => 'medium', 'task_type' => 'inspection', 'is_recurring' => true, 'recurrence_type' => 'weekly', 'recurrence_interval' => 1],
            ['owner_id' => $demo1->id, 'title' => 'Hoof care routine', 'description' => 'Check and clean hooves of all breeding camels', 'priority' => 'medium', 'task_type' => 'medical', 'is_recurring' => true, 'recurrence_type' => 'monthly', 'recurrence_interval' => 1],
            ['owner_id' => $demo2->id, 'title' => 'Vaccination schedule review', 'description' => 'Review upcoming vaccinations and order supplies', 'priority' => 'high', 'task_type' => 'vaccination', 'is_recurring' => true, 'recurrence_type' => 'monthly', 'recurrence_interval' => 1],
        ];
        foreach ($predefinedTasks as $pt) {
            PredefinedTask::create($pt);
        }

        // ──────────────────────────────────────────────
        // 14. TASK LOGS
        // ──────────────────────────────────────────────
        $completedTasks = Task::where('status', 'completed')->get();
        foreach ($completedTasks as $ct) {
            TaskLog::create([
                'task_id' => $ct->id,
                'user_id' => $ct->assigned_to,
                'log_type' => 'note',
                'description' => "Task '{$ct->title}' completed successfully.",
                'status' => 'submitted',
                'created_at' => $ct->completed_at ?? now()->subDay(),
                'updated_at' => $ct->completed_at ?? now()->subDay(),
            ]);
        }

        $inProgressTasks = Task::where('status', 'in_progress')->get();
        foreach ($inProgressTasks as $ipt) {
            TaskLog::create([
                'task_id' => $ipt->id,
                'user_id' => $ipt->assigned_to,
                'log_type' => 'note',
                'description' => "Started working on '{$ipt->title}'. Initial assessment done.",
                'status' => 'submitted',
                'created_at' => now()->subHours(rand(1, 6)),
                'updated_at' => now()->subHours(rand(1, 6)),
            ]);
        }

        $deliveredTasks = Task::where('status', 'delivered')->get();
        foreach ($deliveredTasks as $dt) {
            TaskLog::create([
                'task_id' => $dt->id,
                'user_id' => $dt->assigned_to,
                'log_type' => 'note',
                'description' => "Task '{$dt->title}' delivered for review.",
                'status' => 'submitted',
                'location_lat' => $dt->animal?->device?->gps_lat,
                'location_lng' => $dt->animal?->device?->gps_lng,
                'created_at' => $dt->delivered_at ?? now()->subHours(8),
                'updated_at' => $dt->delivered_at ?? now()->subHours(8),
            ]);
        }

        // ──────────────────────────────────────────────
        // 15. NOTIFICATIONS
        // ──────────────────────────────────────────────
        $notifications = [
            ['user_id' => $demo1->id, 'type' => 'geofence_alert', 'title' => 'Geofence Alert', 'body' => 'Noora has exited Ahmed North Paddock. Immediate attention required.', 'data' => ['animal_id' => $animal2->id, 'alert_type' => 'exit'], 'read_at' => null],
            ['user_id' => $demo1->id, 'type' => 'task_assigned', 'title' => 'New Task Assigned', 'body' => 'Morning feeding - Sultan has been assigned to Youssef.', 'data' => ['task_id' => $tasks[0]['title'] ?? null], 'read_at' => null],
            ['user_id' => $demo1->id, 'type' => 'auction_bid', 'title' => 'New Bid Received', 'body' => 'Sara Al-Demo placed a bid of 46,000 SAR on Suhail Racing Camel.', 'data' => ['auction_id' => 1], 'read_at' => now()->subDays(1)],
            ['user_id' => $demo1->id, 'type' => 'system', 'title' => 'Device Battery Low', 'body' => 'Device DMO-0003 (AsiL) battery is at 23%. Consider recharging.', 'data' => ['device_id' => 'DMO-0003'], 'read_at' => null],
            ['user_id' => $demo1->id, 'type' => 'transfer', 'title' => 'Transfer Request Received', 'body' => 'Sara has proposed transferring Zafir to you for 8,500 SAR.', 'data' => ['transfer_id' => 2], 'read_at' => null],
            ['user_id' => $demo2->id, 'type' => 'geofence_alert', 'title' => 'Geofence Alert', 'body' => 'Zafir has exited Sara South Paddock.', 'data' => ['animal_id' => $animal5->id, 'alert_type' => 'exit'], 'read_at' => now()->subDays(1)],
            ['user_id' => $demo2->id, 'type' => 'message', 'title' => 'New Message', 'body' => 'Ahmed Al-Demo sent you a message about the Majaheem breeding female.', 'data' => ['conversation_id' => 1], 'read_at' => null],
            ['user_id' => $demo2->id, 'type' => 'auction_bid', 'title' => 'Bid Placed on Your Auction', 'body' => 'Ahmed Al-Demo placed a bid of 3,200 SAR on Boer Goat Buck.', 'data' => ['auction_id' => 3], 'read_at' => null],
            ['user_id' => $demo2->id, 'type' => 'system', 'title' => 'Vaccination Due Soon', 'body' => 'Zafir (DMO-005) has a scheduled vaccination in 15 days.', 'data' => ['animal_id' => $animal5->id], 'read_at' => null],
            ['user_id' => $demo2->id, 'type' => 'system', 'title' => 'Quarantine Alert', 'body' => 'Bashir has entered the Quarantine Zone. Please investigate.', 'data' => ['animal_id' => $animal9->id], 'read_at' => null],
            ['user_id' => $shepherd1->id, 'type' => 'task_assigned', 'title' => 'New Task: Sahm hoof treatment', 'body' => 'Continue hoof rot treatment and re-bandage', 'data' => ['task_id' => 15], 'read_at' => null],
            ['user_id' => $shepherd2->id, 'type' => 'task_assigned', 'title' => 'Task Delivered', 'body' => 'Bashir milking schedule has been delivered for your review.', 'data' => ['task_id' => 15], 'read_at' => null],
            ['user_id' => $adminUser->id, 'type' => 'system', 'title' => 'New User Registration', 'body' => 'Ahmed Al-Demo (demo1@oasis.com) has registered as a new owner.', 'data' => ['user_id' => $demo1->id], 'read_at' => null],
            ['user_id' => $adminUser->id, 'type' => 'subscription_purchased', 'title' => 'Subscription Purchased', 'body' => 'Sara Al-Demo purchased the Business plan for 1,999 SAR/month.', 'data' => ['user_id' => $demo2->id], 'read_at' => null],
            ['user_id' => $adminUser->id, 'type' => 'auction_new', 'title' => 'New Auction Created', 'body' => 'Ahmed Al-Demo created a new auction "Suhail Racing Camel - AsiL" starting at 45,000 SAR.', 'data' => ['auction_id' => 1], 'read_at' => null],
            ['user_id' => $adminUser->id, 'type' => 'system', 'title' => 'Task Completed', 'body' => 'Youssef Shepherd has completed "Quarterly report review" ahead of schedule.', 'data' => ['task_id' => 7], 'read_at' => now()->subHours(12)],
            ['user_id' => $adminUser->id, 'type' => 'subscription_expiring', 'title' => 'Subscription Expiring', 'body' => 'User Sara Al-Demo subscription expires in 5 days.', 'data' => ['user_id' => $demo2->id], 'read_at' => null],
            ['user_id' => $adminUser->id, 'type' => 'system', 'title' => 'Geofence Alert', 'body' => 'Noora has exited Ahmed North Paddock. Immediate attention required.', 'data' => ['animal_id' => $animal2->id], 'read_at' => null],
        ];
        foreach ($notifications as $n) {
            Notification::create($n);
        }

        // ──────────────────────────────────────────────
        // 16. AUCTIONS & BIDS
        // ──────────────────────────────────────────────
        $auctions = [
            [
                'title' => 'Suhail Racing Camel - AsiL (DMO-003)',
                'description' => 'Young male racing camel with exceptional speed. Currently in training for upcoming season.',
                'animal_id' => $animal3->id, 'owner_id' => $demo1->id,
                'starting_price' => 45000, 'reserve_price' => 55000,
                'status' => 'active', 'current_price' => 47500,
                'starts_at' => now()->subDays(2), 'ends_at' => now()->addDays(10),
            ],
            [
                'title' => 'Majaheem Breeding Male - Sultan (DMO-001)',
                'description' => 'Premium breeding male with excellent lineage. Proven fertility with 3 successful seasons.',
                'animal_id' => $animal1->id, 'owner_id' => $demo1->id,
                'starting_price' => 35000,
                'status' => 'draft',
                'starts_at' => now(), 'ends_at' => now()->addDays(14),
            ],
            [
                'title' => 'Boer Goat Buck - Zafir (DMO-005)',
                'description' => 'Young purebred Boer goat buck for breeding.',
                'animal_id' => $animal5->id, 'owner_id' => $demo2->id,
                'starting_price' => 3000, 'reserve_price' => 4500, 'current_price' => 3200,
                'status' => 'active',
                'starts_at' => now()->subDays(1), 'ends_at' => now()->addDays(20),
            ],
            [
                'title' => 'Arabian Mare - Jood (DMO-010) — SOLD',
                'description' => 'Purebred Arabian mare with champion bloodline. Winner of 3 regional shows.',
                'animal_id' => $animal10->id, 'owner_id' => $demo1->id,
                'starting_price' => 85000, 'reserve_price' => 90000, 'current_price' => 92000,
                'status' => 'sold', 'winner_id' => $demo2->id,
                'payment_status' => 'pending', 'payment_expires_at' => now()->addDays(7),
                'starts_at' => now()->subDays(14), 'ends_at' => now()->subDays(1),
                'ended_at' => now()->subDays(1),
            ],
            [
                'title' => 'Wadhah Female - Noora (DMO-002) — Ended',
                'description' => 'Experienced breeding female. Did not meet reserve price.',
                'animal_id' => $animal2->id, 'owner_id' => $demo1->id,
                'starting_price' => 28000, 'reserve_price' => 35000, 'current_price' => 32000,
                'status' => 'ended',
                'starts_at' => now()->subDays(7), 'ends_at' => now()->subDays(1),
                'ended_at' => now()->subDays(1),
            ],
        ];
        foreach ($auctions as $a) {
            Auction::create($a);
        }
        $allAuctions = Auction::all();

        $suhailAuction = $allAuctions->where('status', 'active')->where('animal_id', $animal3->id)->first();
        $goatAuction = $allAuctions->where('status', 'active')->where('animal_id', $animal5->id)->first();
        $soldAuction = $allAuctions->where('status', 'sold')->first();
        $endedAuction = $allAuctions->where('status', 'ended')->first();

        $bidData = [];
        if ($suhailAuction) {
            $bidData[] = ['auction_id' => $suhailAuction->id, 'user_id' => $demo2->id, 'amount' => 46000, 'bidder_name' => 'Sara Al-Demo', 'bid_at' => now()->subDays(1), 'is_winning' => false];
            $bidData[] = ['auction_id' => $suhailAuction->id, 'user_id' => $supportUser->id, 'amount' => 45500, 'bidder_name' => 'Khalid Support', 'bid_at' => now()->subDays(2), 'is_winning' => false];
            $bidData[] = ['auction_id' => $suhailAuction->id, 'user_id' => $managerUser->id, 'amount' => 47000, 'bidder_name' => 'Faisal Manager', 'bid_at' => now()->subHours(6), 'is_winning' => true];
            $bidData[] = ['auction_id' => $suhailAuction->id, 'user_id' => $demo2->id, 'amount' => 47500, 'bidder_name' => 'Sara Al-Demo', 'bid_at' => now()->subHours(2), 'is_winning' => true];
        }
        if ($goatAuction) {
            $bidData[] = ['auction_id' => $goatAuction->id, 'user_id' => $demo1->id, 'amount' => 3200, 'bidder_name' => 'Ahmed Al-Demo', 'bid_at' => now()->subHours(12), 'is_winning' => true];
        }
        if ($soldAuction) {
            $bidData[] = ['auction_id' => $soldAuction->id, 'user_id' => $demo2->id, 'amount' => 88000, 'bidder_name' => 'Sara Al-Demo', 'bid_at' => now()->subDays(5), 'is_winning' => false];
            $bidData[] = ['auction_id' => $soldAuction->id, 'user_id' => $supportUser->id, 'amount' => 90000, 'bidder_name' => 'Khalid Support', 'bid_at' => now()->subDays(4), 'is_winning' => false];
            $bidData[] = ['auction_id' => $soldAuction->id, 'user_id' => $demo2->id, 'amount' => 92000, 'bidder_name' => 'Sara Al-Demo', 'bid_at' => now()->subDays(2), 'is_winning' => true];
        }
        if ($endedAuction) {
            $bidData[] = ['auction_id' => $endedAuction->id, 'user_id' => $demo2->id, 'amount' => 30000, 'bidder_name' => 'Sara Al-Demo', 'bid_at' => now()->subDays(6), 'is_winning' => false];
            $bidData[] = ['auction_id' => $endedAuction->id, 'user_id' => $supportUser->id, 'amount' => 32000, 'bidder_name' => 'Khalid Support', 'bid_at' => now()->subDays(5), 'is_winning' => true];
        }
        foreach ($bidData as $b) {
            Bid::create($b);
        }

        // ──────────────────────────────────────────────
        // 17. CONVERSATIONS
        // ──────────────────────────────────────────────

        $conversation1 = Conversation::create(['created_by_id' => $demo1->id, 'type' => 'direct', 'subject' => null, 'status' => 'active']);
        $conversation1->participants()->sync([$demo1->id, $demo2->id]);
        Message::create(['conversation_id' => $conversation1->id, 'sender_id' => $demo1->id, 'body' => 'Hello Sara! I saw you are interested in the Majaheem breeding female. Would you like to discuss a transfer?']);
        Message::create(['conversation_id' => $conversation1->id, 'sender_id' => $demo2->id, 'body' => 'Hi Ahmed! Yes, I am very interested. What price are you thinking?']);
        Message::create(['conversation_id' => $conversation1->id, 'sender_id' => $demo1->id, 'body' => 'I was thinking around 15,000 SAR. She is one of my best breeders.']);
        Message::create(['conversation_id' => $conversation1->id, 'sender_id' => $demo2->id, 'body' => 'That sounds reasonable. Let me check with my team and get back to you.']);
        Message::create(['conversation_id' => $conversation1->id, 'sender_id' => $demo1->id, 'body' => 'Sure, take your time. I also have another Wadhah female if you are interested.']);
        Message::create(['conversation_id' => $conversation1->id, 'sender_id' => $demo2->id, 'body' => 'Really? Send me the details when you can!']);

        $conversation2 = Conversation::create(['created_by_id' => $demo1->id, 'type' => 'direct', 'subject' => 'Support Request: Device DMO-0003 signal issues', 'status' => 'active']);
        $conversation2->participants()->sync([$demo1->id, $supportUser->id]);
        Message::create(['conversation_id' => $conversation2->id, 'sender_id' => $demo1->id, 'body' => 'Hi Khalid, my Suhail camel (AsiL / DMO-003) has been showing low signal on the GPS tracker since yesterday. Can you help troubleshoot?']);
        Message::create(['conversation_id' => $conversation2->id, 'sender_id' => $supportUser->id, 'body' => 'Hello Ahmed, sorry to hear that. Let me check the device status. Can you confirm the device ID is DMO-0003?']);
        Message::create(['conversation_id' => $conversation2->id, 'sender_id' => $demo1->id, 'body' => 'Yes, that is correct. The device was working fine until yesterday afternoon.']);
        Message::create(['conversation_id' => $conversation2->id, 'sender_id' => $supportUser->id, 'body' => 'I can see the device battery is at 23% which is quite low. It might be going into power-saving mode. Could you try replacing the battery or bringing the animal closer to the base station?']);

        $conversation3 = Conversation::create(['created_by_id' => $adminUser?->id ?? 1, 'type' => 'direct', 'subject' => 'Welcome to Oasis Trace', 'status' => 'active']);
        $conversation3->participants()->sync([$adminUser?->id ?? 1, $demo2->id]);
        Message::create(['conversation_id' => $conversation3->id, 'sender_id' => $adminUser?->id ?? 1, 'body' => 'Welcome Sara! We are glad to have you on Oasis Trace. How are you liking the platform so far?']);
        Message::create(['conversation_id' => $conversation3->id, 'sender_id' => $demo2->id, 'body' => 'Thank you! The platform is wonderful. I love the real-time tracking feature. I have a question about setting up geofence alerts though.']);
        Message::create(['conversation_id' => $conversation3->id, 'sender_id' => $adminUser?->id ?? 1, 'body' => 'Happy to help! Geofence alerts are configured from the Geofences page. You can set entry and exit alerts for each zone. Would you like me to walk you through it?']);

        $conversation4 = Conversation::create(['created_by_id' => $demo2->id, 'type' => 'direct', 'subject' => 'Payment for Jood Auction', 'status' => 'active']);
        $conversation4->participants()->sync([$demo2->id, $adminUser?->id ?? 1]);
        Message::create(['conversation_id' => $conversation4->id, 'sender_id' => $demo2->id, 'body' => 'Hi, I won the auction for Jood the Arabian mare. Can you guide me through the payment process?']);
        Message::create(['conversation_id' => $conversation4->id, 'sender_id' => $adminUser?->id ?? 1, 'body' => 'Congratulations Sara! You can make the payment via the auction details page. We accept card payments and bank transfers. The total amount is 92,000 SAR.']);

        $conversation5 = Conversation::create(['created_by_id' => $demo1->id, 'type' => 'ticket', 'subject' => 'Device DMO-0007 offline', 'status' => 'open', 'priority' => 'high', 'assigned_to_id' => $supportUser->id]);
        $conversation5->participants()->sync([$demo1->id, $supportUser->id]);
        Message::create(['conversation_id' => $conversation5->id, 'sender_id' => $demo1->id, 'body' => 'My device DMO-0007 (on Sahm the sheep) has been offline for 4 hours now. Can someone investigate?']);

        // ──────────────────────────────────────────────
        // 18. TRANSFERS (all statuses for testing)
        // ──────────────────────────────────────────────

        // Transfer 1 — Completed: Noora (DMO-002) demo1 → demo2
        // Ahmed sold a Wadhah breeding female to Sara. Commission paid in full.
        $transfer1 = OwnershipTransfer::create([
            'from_user_id' => $demo1->id, 'to_user_id' => $demo2->id,
            'status' => 'completed', 'transfer_type' => 'manual',
            'agreed_price' => 15000.00, 'commission_percentage' => 5.00,
            'commission_amount' => 750.00, 'commission_paid' => true,
            'notes' => 'Transfer of Wadhah breeding female Noora as agreed.',
            'accepted_at' => now()->subDays(14), 'completed_at' => now()->subDays(12),
        ]);
        OwnershipTransferAnimal::create(['ownership_transfer_id' => $transfer1->id, 'animal_id' => $animal2->id]);
        OwnershipHistory::create([
            'animal_id' => $animal2->id, 'from_user_id' => $demo1->id, 'to_user_id' => $demo2->id,
            'transfer_id' => $transfer1->id,
            'transfer_type' => 'manual', 'reference_type' => 'ownership_transfer', 'reference_id' => $transfer1->id,
            'commission_amount' => 750.00, 'agreed_price' => 15000.00,
            'created_at' => now()->subDays(12),
        ]);
        $animal2->update(['owner_id' => $demo2->id]);

        // Transfer 2 — Pending: Zafir (DMO-005) demo2 → demo1
        // Sara proposed selling her Boer goat buck to Ahmed for cross-breeding.
        $transfer2 = OwnershipTransfer::create([
            'from_user_id' => $demo2->id, 'to_user_id' => $demo1->id,
            'status' => 'pending', 'transfer_type' => 'manual',
            'agreed_price' => 8500.00, 'commission_percentage' => 5.00,
            'commission_amount' => 425.00,
            'notes' => 'Proposed transfer of Boer goat buck DMO-005 to Ahmed for cross-breeding program.',
            'expires_at' => now()->addDays(7),
        ]);
        OwnershipTransferAnimal::create(['ownership_transfer_id' => $transfer2->id, 'animal_id' => $animal5->id]);

        // Transfer 3 — Rejected: Sahm (DMO-007) demo2 → demo1
        // Ahmed wanted to buy Sahm the Awassi sheep, but Sara rejected after a health issue was found.
        $transfer3 = OwnershipTransfer::create([
            'from_user_id' => $demo2->id, 'to_user_id' => $demo1->id,
            'status' => 'rejected', 'transfer_type' => 'manual',
            'agreed_price' => 12000.00, 'commission_percentage' => 5.00,
            'commission_amount' => 600.00,
            'notes' => 'Proposed transfer of Awassi ram Sahm to Ahmed.',
            'rejection_reason' => 'The animal has a hoof rot condition that needs treatment first. Will re-list once healthy.',
            'accepted_at' => null, 'completed_at' => null,
            'expires_at' => now()->subDays(3),
        ]);
        OwnershipTransferAnimal::create(['ownership_transfer_id' => $transfer3->id, 'animal_id' => $animal7->id]);

        // Transfer 4 — Cancelled: Rayyan (DMO-008) demo1 → demo2
        // Ahmed started a transfer for his Najdi ewe but changed his mind.
        $transfer4 = OwnershipTransfer::create([
            'from_user_id' => $demo1->id, 'to_user_id' => $demo2->id,
            'status' => 'cancelled', 'transfer_type' => 'manual',
            'agreed_price' => 6500.00, 'commission_percentage' => 5.00,
            'commission_amount' => 325.00,
            'notes' => 'Proposed transfer of Najdi ewe Rayyan to Sara.',
            'accepted_at' => null, 'completed_at' => null,
            'expires_at' => now()->addDays(14),
        ]);
        OwnershipTransferAnimal::create(['ownership_transfer_id' => $transfer4->id, 'animal_id' => $animal8->id]);

        // Transfer 5 — Expired: Bashir (DMO-009) demo2 → demo1
        // Sara offered her Holstein cow but the offer expired without response.
        $transfer5 = OwnershipTransfer::create([
            'from_user_id' => $demo2->id, 'to_user_id' => $demo1->id,
            'status' => 'expired', 'transfer_type' => 'manual',
            'agreed_price' => 18000.00, 'commission_percentage' => 5.00,
            'commission_amount' => 900.00,
            'notes' => 'Proposed transfer of Holstein cow Bashir to Ahmed.',
            'accepted_at' => null, 'completed_at' => null,
            'expires_at' => now()->subDays(5),
        ]);
        OwnershipTransferAnimal::create(['ownership_transfer_id' => $transfer5->id, 'animal_id' => $animal9->id]);

        // Transfer 6 — Auction-type completed: Jood (DMO-010) via auction #4
        // After Sara won and paid for Jood the Arabian mare, an auction-type transfer was auto-created
        $soldAuctionModel = $allAuctions->where('status', 'sold')->first();
        if ($soldAuctionModel) {
            $transfer6 = OwnershipTransfer::create([
                'from_user_id' => $demo1->id,
                'to_user_id' => $demo2->id,
                'status' => 'completed',
                'transfer_type' => 'auction',
                'reference_type' => 'auction',
                'reference_id' => $soldAuctionModel->id,
                'agreed_price' => 92000.00,
                'commission_percentage' => 5.00,
                'commission_amount' => 4600.00,
                'commission_paid' => true,
                'notes' => 'Auto-created via auction payment verification for Arabian Mare - Jood (DMO-010).',
                'accepted_at' => now()->subDays(2),
                'completed_at' => now()->subDays(2),
            ]);
            OwnershipTransferAnimal::create(['ownership_transfer_id' => $transfer6->id, 'animal_id' => $animal10->id]);
            OwnershipHistory::create([
                'animal_id' => $animal10->id,
                'from_user_id' => $demo1->id,
                'to_user_id' => $demo2->id,
                'transfer_id' => $transfer6->id,
                'transfer_type' => 'auction',
                'reference_type' => 'auction',
                'reference_id' => $soldAuctionModel->id,
                'commission_amount' => 4600.00,
                'agreed_price' => 92000.00,
                'created_at' => now()->subDays(2),
            ]);
            $animal10->update(['owner_id' => $demo2->id]);
        }

        // Transfer 7 — Group transfer completed: Ahmed Premium Herd (Group 1) demo1 → demo2
        // Ahmed transferred his entire "Ahmed Premium Herd" group (Sultan + Noora) to Sara
        $group1 = AnimalGroup::where('name', 'Ahmed Premium Herd')->first();
        if ($group1) {
            $groupAnimalIds = $group1->animals()->pluck('animals.id')->toArray();
            $transfer7 = OwnershipTransfer::create([
                'from_user_id' => $demo1->id,
                'to_user_id' => $demo2->id,
                'status' => 'completed',
                'transfer_type' => 'manual',
                'group_id' => $group1->id,
                'agreed_price' => 45000.00,
                'commission_percentage' => 5.00,
                'commission_amount' => 2250.00,
                'commission_paid' => true,
                'notes' => 'Group transfer of entire "Ahmed Premium Herd" (Sultan DMO-001 + Noora DMO-002) as part of joint breeding program.',
                'accepted_at' => now()->subDays(1),
                'completed_at' => now()->subDays(1),
            ]);
            foreach ($groupAnimalIds as $gid) {
                OwnershipTransferAnimal::create(['ownership_transfer_id' => $transfer7->id, 'animal_id' => $gid]);
                OwnershipHistory::create([
                    'animal_id' => $gid,
                    'from_user_id' => $demo1->id,
                    'to_user_id' => $demo2->id,
                    'transfer_id' => $transfer7->id,
                    'transfer_type' => 'manual',
                    'reference_type' => 'ownership_transfer',
                    'reference_id' => $transfer7->id,
                    'commission_amount' => 2250.00,
                    'agreed_price' => 45000.00,
                    'created_at' => now()->subDays(1),
                ]);
                $animal = Animal::find($gid);
                if ($animal) {
                    $animal->update(['owner_id' => $demo2->id]);
                }
            }
        }

        // ──────────────────────────────────────────────
        // 19. SUBSCRIPTIONS & ORDERS
        // ──────────────────────────────────────────────
        foreach ($demoUsers as $demoUser) {
            UserSubscription::create([
                'user_id' => $demoUser->id,
                'tier_id' => $starterTier?->id ?? 1,
                'status' => 'active',
                'started_at' => now()->subDays(30),
                'ends_at' => now()->addDays(335),
                'billing_cycle' => 'yearly',
                'payment_method' => 'card',
                'payment_reference' => 'DEMO-SUB-' . $demoUser->id,
            ]);
        }

        SubscriptionOrder::create([
            'user_id' => $demo1->id, 'tier_id' => $starterTier?->id ?? 1,
            'amount' => $starterTier?->price_yearly ?? 299.99, 'currency' => 'USD',
            'billing_cycle' => 'yearly',
            'shipping_address' => ['full_name' => 'Ahmed Al-Demo', 'street' => '123 King Fahd Road', 'city' => 'Riyadh', 'state' => '', 'zip' => '12345', 'country' => 'Saudi Arabia'],
            'shipping_status' => 'delivered', 'tracking_number' => 'DMO-TRK-001',
            'shipped_at' => now()->subDays(28), 'delivered_at' => now()->subDays(25),
            'payment_method' => 'card', 'payment_status' => 'paid',
            'payment_reference' => 'PAY-DEMO1-' . strtoupper(uniqid()),
        ]);
        SubscriptionOrder::create([
            'user_id' => $demo2->id, 'tier_id' => $starterTier?->id ?? 1,
            'amount' => $starterTier?->price_monthly ?? 29.99, 'currency' => 'USD',
            'billing_cycle' => 'monthly',
            'shipping_address' => ['full_name' => 'Sara Al-Demo', 'street' => '456 Olaya Street', 'city' => 'Riyadh', 'state' => '', 'zip' => '54321', 'country' => 'Saudi Arabia'],
            'shipping_status' => 'shipped', 'tracking_number' => 'DMO-TRK-002',
            'shipped_at' => now()->subDays(2),
            'payment_method' => 'bank_transfer', 'payment_status' => 'paid',
            'payment_reference' => 'BT-DEMO2-' . strtoupper(uniqid()),
            'notes' => 'First subscription - welcome kit',
        ]);
        SubscriptionOrder::create([
            'user_id' => $managerUser->id, 'tier_id' => $freeTier?->id ?? 2,
            'amount' => 0, 'currency' => 'USD',
            'billing_cycle' => 'monthly',
            'shipping_address' => ['full_name' => 'Faisal Manager', 'street' => '789 Business Park', 'city' => 'Riyadh', 'state' => '', 'zip' => '98765', 'country' => 'Saudi Arabia'],
            'shipping_status' => 'pending',
            'payment_method' => 'bank_transfer', 'payment_status' => 'pending',
        ]);

        // ──────────────────────────────────────────────
        // 20. BANNERS
        // ──────────────────────────────────────────────
        Banner::create([
            'type' => 'announcement', 'icon' => 'campaign', 'color_scheme' => 'brand',
            'translations' => [
                'en' => ['title' => 'Welcome to Oasis Trace Demo!', 'description' => 'Explore all features including animal tracking, auctions, transfers, medical records, and more.'],
                'ar' => ['title' => 'مرحباً بك في Oasis Trace التجريبي!', 'description' => 'استكشف جميع الميزات بما في ذلك تتبع الحيوانات والمزادات والتحويلات والسجلات الطبية والمزيد.'],
            ],
            'sort_order' => 10, 'is_active' => true, 'expires_at' => now()->addDays(30),
        ]);
        Banner::create([
            'type' => 'insight', 'icon' => 'lightbulb', 'color_scheme' => 'amber',
            'translations' => [
                'en' => ['title' => 'Pro Tip', 'description' => 'Use the simulator page to test device movements, temperature changes, and battery drain scenarios.'],
                'ar' => ['title' => 'نصيحة', 'description' => 'استخدم صفحة المحاكاة لاختبار حركات الجهاز وتغيرات درجة الحرارة وسيناريوهات استنزاف البطارية.'],
            ],
            'sort_order' => 5, 'is_active' => true, 'expires_at' => now()->addDays(60),
        ]);
        Banner::create([
            'type' => 'cta', 'icon' => 'local_hospital', 'color_scheme' => 'brand',
            'translations' => [
                'en' => ['title' => 'Need a Vet?', 'description' => 'Connect with regional veterinary experts for complex procedures.', 'button_text' => 'Find a Vet'],
                'ar' => ['title' => 'تحتاج طبيب بيطري؟', 'description' => 'تواصل مع خبراء الطب البيطري الإقليميين للإجراءات المعقدة.', 'button_text' => 'ابحث عن طبيب'],
            ],
            'button_url' => '/medical-records',
            'sort_order' => 15, 'is_active' => true, 'expires_at' => now()->addDays(90),
        ]);

        // ──────────────────────────────────────────────
        // 21. SETTINGS
        // ──────────────────────────────────────────────
        Setting::updateOrCreate(['key' => 'auction_auto_approve'], ['value' => '0']);
        Setting::updateOrCreate(['key' => 'transfer_commission_enabled'], ['value' => '1']);
        Setting::updateOrCreate(['key' => 'transfer_commission_type'], ['value' => 'percentage']);
        Setting::updateOrCreate(['key' => 'transfer_commission_percentage'], ['value' => '5']);
        Setting::updateOrCreate(['key' => 'demo_mode'], ['value' => '1']);

        // ──────────────────────────────────────────────
        // SUMMARY
        // ──────────────────────────────────────────────
        $this->command->info('Demo data created:');
        $this->command->info('  5 species + ' . Breed::count() . ' breeds');
        $this->command->info('  ' . TaskType::count() . ' task types, ' . MedicalRecordType::count() . ' medical record types, ' . VaccinationType::count() . ' vaccination types');
        $this->command->info('  2 demo owners (Ahmed + Sara) + Manager + Doctor + 3 staff + 2 shepherds');
        $this->command->info('  ' . Animal::count() . ' animals with devices + names');
        $this->command->info('  ' . AnimalGroup::count() . ' animal groups');
        $this->command->info('  ' . Geofence::count() . ' geofences + ' . GeofenceAlert::count() . ' alerts');
        $this->command->info('  ' . LocationHistory::count() . ' location history points');
        $this->command->info('  ' . AnimalDocument::count() . ' animal documents');
        $this->command->info('  ' . MedicalRecord::count() . ' medical records');
        $this->command->info('  ' . VaccinationSchedule::count() . ' vaccinations');
        $this->command->info('  ' . Task::count() . ' tasks + ' . PredefinedTask::count() . ' predefined tasks');
        $this->command->info('  ' . TaskLog::count() . ' task logs');
        $this->command->info('  ' . Notification::count() . ' notifications');
        $this->command->info('  ' . Auction::count() . ' auctions (active/draft/sold/ended) + ' . Bid::count() . ' bids');
        $this->command->info('  ' . Conversation::count() . ' conversations (' . Message::count() . ' messages)');
        $this->command->info('  ' . OwnershipTransfer::count() . ' transfers');
        $this->command->info('  ' . UserSubscription::count() . ' subscriptions + ' . SubscriptionOrder::count() . ' orders');
        $this->command->info('  ' . Banner::count() . ' banners');
        $this->command->info('=== DemoDataSeeder complete ===');
    }
}
