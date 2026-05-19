<?php

namespace Database\Seeders;

use App\Models\{
    User, Animal, Device, AnimalGroup, Geofence,
    MedicalRecord, VaccinationSchedule, Task,
    Auction, Conversation, Message,
    OwnershipTransfer, OwnershipTransferAnimal, OwnershipHistory,
    UserSubscription, SubscriptionOrder, Banner, Setting
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
        ];
        foreach ($tables as $table) {
            DB::table($table)->truncate();
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $this->command->info('Truncated all content tables.');

        $ownerRole = Role::where('name', 'Owner')->first();
        $shepherdRole = Role::where('name', 'Shepherd')->first();

        $freeTier = \App\Models\SubscriptionTier::where('slug', 'free')->first();
        $starterTier = \App\Models\SubscriptionTier::where('slug', 'starter')->first();

        $demo1 = User::updateOrCreate(
            ['email' => 'demo1@oasis.com'],
            [
                'name' => 'Ahmed Al-Demo',
                'password' => Hash::make('password'),
                'is_active' => true,
                'phone' => '+966500000001',
            ]
        );
        $demo1->syncRoles([$ownerRole]);

        $demo2 = User::updateOrCreate(
            ['email' => 'demo2@oasis.com'],
            [
                'name' => 'Sara Al-Demo',
                'password' => Hash::make('password'),
                'is_active' => true,
                'phone' => '+966500000002',
            ]
        );
        $demo2->syncRoles([$ownerRole]);

        $shepherd1 = User::updateOrCreate(
            ['email' => 'youssef@demo.com'],
            [
                'name' => 'Youssef Shepherd',
                'password' => Hash::make('password'),
                'is_active' => true,
                'phone' => '+966500000003',
                'managed_by' => $demo1->id,
            ]
        );
        $shepherd1->syncRoles([$shepherdRole]);

        $shepherd2 = User::updateOrCreate(
            ['email' => 'hassan@demo.com'],
            [
                'name' => 'Hassan Shepherd',
                'password' => Hash::make('password'),
                'is_active' => true,
                'phone' => '+966500000004',
                'managed_by' => $demo2->id,
            ]
        );
        $shepherd2->syncRoles([$shepherdRole]);

        $supportRole = Role::where('name', 'Support')->first();
        $accountantRole = Role::where('name', 'Accountant')->first();
        $csRole = Role::where('name', 'Customer Service')->first();

        $supportUser = User::updateOrCreate(
            ['email' => 'support@oasis.com'],
            [
                'name' => 'Khalid Support',
                'password' => Hash::make('password'),
                'is_active' => true,
                'phone' => '+966500000005',
            ]
        );
        $supportUser->syncRoles([$supportRole]);

        $accountantUser = User::updateOrCreate(
            ['email' => 'accounts@oasis.com'],
            [
                'name' => 'Mona Accountant',
                'password' => Hash::make('password'),
                'is_active' => true,
                'phone' => '+966500000006',
            ]
        );
        $accountantUser->syncRoles([$accountantRole]);

        $csUser = User::updateOrCreate(
            ['email' => 'cs@oasis.com'],
            [
                'name' => 'Nora Customer Service',
                'password' => Hash::make('password'),
                'is_active' => true,
                'phone' => '+966500000007',
            ]
        );
        $csUser->syncRoles([$csRole]);

        $demoUsers = [$demo1, $demo2];
        $demoShepherds = [1 => $shepherd1, 2 => $shepherd2];

        $deviceData = [
            ['device_id' => 'DMO-0001', 'firmware_version' => 'v2.5.0', 'status' => 'online', 'battery_level' => 92, 'gps_lat' => 24.7136, 'gps_lng' => 46.6753, 'last_ping' => now()->subMinutes(3), 'temperature' => 38.6],
            ['device_id' => 'DMO-0002', 'firmware_version' => 'v2.5.0', 'status' => 'online', 'battery_level' => 88, 'gps_lat' => 24.7140, 'gps_lng' => 46.6760, 'last_ping' => now()->subMinutes(1), 'temperature' => 38.3],
            ['device_id' => 'DMO-0003', 'firmware_version' => 'v2.4.1', 'status' => 'online', 'battery_level' => 76, 'gps_lat' => 24.7145, 'gps_lng' => 46.6765, 'last_ping' => now()->subMinutes(7), 'temperature' => 38.9],
            ['device_id' => 'DMO-0004', 'firmware_version' => 'v2.5.0', 'status' => 'online', 'battery_level' => 95, 'gps_lat' => 24.7150, 'gps_lng' => 46.6770, 'last_ping' => now()->subMinutes(2), 'temperature' => 38.2],
            ['device_id' => 'DMO-0005', 'firmware_version' => 'v2.4.1', 'status' => 'low_signal', 'battery_level' => 23, 'gps_lat' => 24.7155, 'gps_lng' => 46.6775, 'last_ping' => now()->subMinutes(20), 'temperature' => 39.6],
        ];
        foreach ($deviceData as $d) {
            Device::create($d);
        }
        $allDevices = Device::whereIn('device_id', array_column($deviceData, 'device_id'))->get()->keyBy('device_id');

        $animalData = [
            ['animal_id' => 'DMO-001', 'species' => 'Camel', 'breed' => 'Majaheem', 'gender' => 'Male', 'date_of_birth' => '2021-05-10', 'color_markings' => 'White with brown patches', 'current_weight' => 680, 'baseline_temperature' => 38.5, 'owner_id' => $demo1->id],
            ['animal_id' => 'DMO-002', 'species' => 'Camel', 'breed' => 'Wadhah', 'gender' => 'Female', 'date_of_birth' => '2022-08-15', 'color_markings' => 'Golden brown', 'current_weight' => 590, 'baseline_temperature' => 38.2, 'owner_id' => $demo1->id],
            ['animal_id' => 'DMO-003', 'species' => 'Camel', 'breed' => 'Suhail', 'gender' => 'Male', 'date_of_birth' => '2023-02-20', 'color_markings' => 'Dark brown, white legs', 'current_weight' => 520, 'baseline_temperature' => 38.8, 'owner_id' => $demo1->id],
            ['animal_id' => 'DMO-004', 'species' => 'Camel', 'breed' => 'Majaheem', 'gender' => 'Female', 'date_of_birth' => '2021-11-05', 'color_markings' => 'Cream colored', 'current_weight' => 620, 'baseline_temperature' => 38.4, 'owner_id' => $demo2->id],
            ['animal_id' => 'DMO-005', 'species' => 'Goat', 'breed' => 'Boer', 'gender' => 'Male', 'date_of_birth' => '2024-01-10', 'color_markings' => 'White with brown head', 'current_weight' => 88, 'baseline_temperature' => 39.5, 'owner_id' => $demo2->id],
        ];
        foreach ($animalData as $a) {
            Animal::create($a);
        }
        $allAnimals = Animal::whereIn('animal_id', array_column($animalData, 'animal_id'))->get()->keyBy('animal_id');

        $deviceAnimalMap = ['DMO-0001' => 'DMO-001', 'DMO-0002' => 'DMO-002', 'DMO-0003' => 'DMO-003', 'DMO-0004' => 'DMO-004', 'DMO-0005' => 'DMO-005'];
        foreach ($allDevices as $device) {
            $animalIdKey = $deviceAnimalMap[$device->device_id] ?? null;
            $animal = $animalIdKey ? $allAnimals->get($animalIdKey) : null;
            $device->animal_id = $animal?->id;
            $device->owner_id = $animal?->owner_id ?? $demo1->id;
            $device->save();
        }

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

        $group2 = AnimalGroup::create([
            'name' => 'Young Racers',
            'description' => 'Young racing camels in training',
            'color' => '#3b82f6',
            'owner_id' => $demo1->id,
        ]);
        $group2->animals()->sync([
            $allAnimals->get('DMO-003')->id,
        ]);

        $group3 = AnimalGroup::create([
            'name' => 'Sara Mixed Herd',
            'description' => 'Sara Al-Demo animals',
            'color' => '#f59e0b',
            'owner_id' => $demo2->id,
        ]);
        $group3->animals()->sync([
            $allAnimals->get('DMO-004')->id,
            $allAnimals->get('DMO-005')->id,
        ]);

        $geofences = [
            ['name' => 'Ahmed North Paddock', 'coordinates' => json_encode([[24.7136, 46.6753], [24.7136, 46.6853], [24.7036, 46.6853], [24.7036, 46.6753], [24.7136, 46.6753]]), 'color' => '#22C55E', 'alert_type' => 'both', 'is_active' => true, 'owner_id' => $demo1->id],
            ['name' => 'Sara South Paddock', 'coordinates' => json_encode([[24.7200, 46.6800], [24.7200, 46.6900], [24.7100, 46.6900], [24.7100, 46.6800], [24.7200, 46.6800]]), 'color' => '#3B82F6', 'alert_type' => 'exit', 'is_active' => true, 'owner_id' => $demo2->id],
        ];
        foreach ($geofences as $g) {
            Geofence::create($g);
        }

        $animal1 = $allAnimals->get('DMO-001');
        $animal2 = $allAnimals->get('DMO-002');
        $animal3 = $allAnimals->get('DMO-003');
        $animal4 = $allAnimals->get('DMO-004');
        $animal5 = $allAnimals->get('DMO-005');

        $medicals = [
            ['animal_id' => $animal1->id, 'owner_id' => $demo1->id, 'record_type' => 'checkup', 'title' => 'Routine health check', 'description' => 'Annual health assessment. All vitals normal.', 'record_date' => now()->subDays(45), 'veterinarian' => 'Dr. Fatima Al-Said', 'status' => 'completed'],
            ['animal_id' => $animal1->id, 'owner_id' => $demo1->id, 'record_type' => 'treatment', 'title' => 'Hoof trimming', 'description' => 'Routine hoof maintenance.', 'record_date' => now()->subDays(20), 'veterinarian' => 'Dr. Fatima Al-Said', 'status' => 'completed'],
            ['animal_id' => $animal2->id, 'owner_id' => $demo1->id, 'record_type' => 'checkup', 'title' => 'Pre-breeding check', 'description' => 'Health evaluation before breeding season.', 'record_date' => now()->subDays(30), 'veterinarian' => 'Dr. Fatima Al-Said', 'status' => 'completed'],
            ['animal_id' => $animal3->id, 'owner_id' => $demo1->id, 'record_type' => 'checkup', 'title' => 'Racing fitness assessment', 'description' => 'Cardiovascular and respiratory check.', 'record_date' => now()->subDays(15), 'veterinarian' => 'Dr. Fatima Al-Said', 'status' => 'completed', 'health_status' => 'excellent'],
            ['animal_id' => $animal4->id, 'owner_id' => $demo2->id, 'record_type' => 'checkup', 'title' => 'Routine health check', 'description' => 'Annual checkup. All vitals normal.', 'record_date' => now()->subDays(60), 'veterinarian' => 'Dr. Fatima Al-Said', 'status' => 'completed'],
            ['animal_id' => $animal4->id, 'owner_id' => $demo2->id, 'record_type' => 'treatment', 'title' => 'Deworming', 'description' => 'Routine deworming treatment.', 'record_date' => now()->subDays(10), 'status' => 'completed', 'medication' => 'Ivermectin', 'dosage' => '10ml'],
            ['animal_id' => $animal5->id, 'owner_id' => $demo2->id, 'record_type' => 'emergency', 'title' => 'Minor injury treatment', 'description' => 'Small cut on leg treated and bandaged.', 'record_date' => now()->subDays(5), 'status' => 'completed', 'notes' => 'Healing well, bandage change in 3 days.'],
        ];
        foreach ($medicals as $m) {
            MedicalRecord::create($m);
        }

        $vaccinations = [
            ['animal_id' => $animal1->id, 'owner_id' => $demo1->id, 'vaccine_name' => 'Clostridial 5-in-1', 'scheduled_date' => now()->subDays(90), 'administered_date' => now()->subDays(90), 'status' => 'administered', 'veterinarian' => 'Dr. Fatima Al-Said', 'dose_number' => 1, 'total_doses' => 1],
            ['animal_id' => $animal2->id, 'owner_id' => $demo1->id, 'vaccine_name' => 'Clostridial 5-in-1', 'scheduled_date' => now()->subDays(90), 'administered_date' => now()->subDays(90), 'status' => 'administered', 'veterinarian' => 'Dr. Fatima Al-Said', 'dose_number' => 1, 'total_doses' => 1],
            ['animal_id' => $animal2->id, 'owner_id' => $demo1->id, 'vaccine_name' => 'Rabies', 'scheduled_date' => now()->subDays(30), 'administered_date' => now()->subDays(30), 'status' => 'administered', 'veterinarian' => 'Dr. Fatima Al-Said'],
            ['animal_id' => $animal3->id, 'owner_id' => $demo1->id, 'vaccine_name' => 'Clostridial 5-in-1', 'scheduled_date' => now()->addDays(60), 'status' => 'scheduled'],
            ['animal_id' => $animal4->id, 'owner_id' => $demo2->id, 'vaccine_name' => 'Clostridial 5-in-1', 'scheduled_date' => now()->subDays(45), 'administered_date' => now()->subDays(45), 'status' => 'administered', 'veterinarian' => 'Dr. Fatima Al-Said'],
            ['animal_id' => $animal5->id, 'owner_id' => $demo2->id, 'vaccine_name' => 'CD-T', 'scheduled_date' => now()->addDays(15), 'status' => 'scheduled'],
        ];
        foreach ($vaccinations as $v) {
            VaccinationSchedule::create($v);
        }

        $tasks = [
            ['owner_id' => $demo1->id, 'assigned_to' => $shepherd1->id, 'animal_id' => $animal1->id, 'title' => 'Morning feeding', 'description' => 'Feed concentrate mix to DMO-001', 'priority' => 'high', 'task_type' => 'feeding', 'status' => 'in_progress', 'due_date' => now()->addHours(2)],
            ['owner_id' => $demo1->id, 'assigned_to' => $shepherd1->id, 'animal_id' => null, 'title' => 'Inspect north paddock fence', 'description' => 'Check for damage after last storm', 'priority' => 'medium', 'task_type' => 'inspection', 'status' => 'pending', 'due_date' => now()->addDays(1)],
            ['owner_id' => $demo1->id, 'assigned_to' => $shepherd1->id, 'animal_id' => $animal3->id, 'title' => 'DMO-003 temperature check', 'description' => 'Monitor temperature after training session', 'priority' => 'high', 'task_type' => 'medical', 'status' => 'pending', 'due_date' => now()->addHours(4)],
            ['owner_id' => $demo2->id, 'assigned_to' => $shepherd2->id, 'animal_id' => $animal5->id, 'title' => 'Bandage change DMO-005', 'description' => 'Change leg bandage and check healing', 'priority' => 'high', 'task_type' => 'medical', 'status' => 'pending', 'due_date' => now()->addDays(2)],
            ['owner_id' => $demo2->id, 'assigned_to' => $shepherd2->id, 'animal_id' => $animal4->id, 'title' => 'Water trough refill', 'description' => 'Refill water troughs in south paddock', 'priority' => 'low', 'task_type' => 'feeding', 'status' => 'completed', 'due_date' => now()->subDays(1)],
        ];
        foreach ($tasks as $t) {
            Task::create($t);
        }

        $conversation = Conversation::create([
            'created_by_id' => $demo1->id,
            'type' => 'direct',
            'subject' => null,
            'status' => 'active',
        ]);
        $conversation->participants()->sync([$demo1->id, $demo2->id]);

        Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $demo1->id,
            'body' => 'Hello Sara! I saw you are interested in the Majaheem breeding female. Would you like to discuss a transfer?',
        ]);
        Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $demo2->id,
            'body' => 'Hi Ahmed! Yes, I am very interested. What price are you thinking?',
        ]);
        Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $demo1->id,
            'body' => 'I was thinking around 15,000 SAR. She is one of my best breeders.',
        ]);
        Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $demo2->id,
            'body' => 'That sounds reasonable. Let me check with my team and get back to you.',
        ]);

        $transfer = OwnershipTransfer::create([
            'from_user_id' => $demo1->id,
            'to_user_id' => $demo2->id,
            'status' => 'completed',
            'transfer_type' => 'manual',
            'agreed_price' => 15000.00,
            'commission_percentage' => 5.00,
            'commission_amount' => 750.00,
            'commission_paid' => true,
            'notes' => 'Transfer of Majaheem breeding female as agreed.',
            'accepted_at' => now()->subDays(14),
            'completed_at' => now()->subDays(12),
        ]);
        OwnershipTransferAnimal::create([
            'ownership_transfer_id' => $transfer->id,
            'animal_id' => $animal4->id,
        ]);
        OwnershipHistory::create([
            'animal_id' => $animal4->id,
            'from_user_id' => $demo1->id,
            'to_user_id' => $demo2->id,
            'transfer_type' => 'manual',
            'reference_type' => 'ownership_transfer',
            'reference_id' => $transfer->id,
        ]);

        $auctions = [
            [
                'title' => 'Suhail Racing Camel - DMO-003',
                'description' => 'Young male racing camel with exceptional speed. Currently in training for upcoming season.',
                'animal_id' => $animal3->id,
                'owner_id' => $demo1->id,
                'starting_price' => 45000,
                'reserve_price' => 55000,
                'status' => 'active',
                'starts_at' => now()->subDays(2),
                'ends_at' => now()->addDays(10),
            ],
            [
                'title' => 'Majaheem Breeding Male - DMO-001',
                'description' => 'Premium breeding male with excellent lineage. Proven fertility with 3 successful seasons.',
                'animal_id' => $animal1->id,
                'owner_id' => $demo1->id,
                'starting_price' => 35000,
                'status' => 'draft',
                'starts_at' => now(),
                'ends_at' => now()->addDays(14),
            ],
            [
                'title' => 'Boer Goat Buck - DMO-005',
                'description' => 'Young purebred Boer goat buck for breeding.',
                'animal_id' => $animal5->id,
                'owner_id' => $demo2->id,
                'starting_price' => 3000,
                'reserve_price' => 4500,
                'status' => 'active',
                'starts_at' => now()->subDays(1),
                'ends_at' => now()->addDays(20),
            ],
        ];
        foreach ($auctions as $a) {
            Auction::create($a);
        }

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
            'user_id' => $demo1->id,
            'tier_id' => $starterTier?->id ?? 1,
            'amount' => $starterTier?->price_yearly ?? 299.99,
            'currency' => 'USD',
            'billing_cycle' => 'yearly',
            'shipping_address' => ['full_name' => 'Ahmed Al-Demo', 'street' => '123 King Fahd Road', 'city' => 'Riyadh', 'state' => '', 'zip' => '12345', 'country' => 'Saudi Arabia'],
            'shipping_status' => 'delivered',
            'tracking_number' => 'DMO-TRK-001',
            'shipped_at' => now()->subDays(28),
            'delivered_at' => now()->subDays(25),
            'payment_method' => 'card',
            'payment_status' => 'paid',
            'payment_reference' => 'PAY-DEMO1-' . strtoupper(uniqid()),
        ]);
        SubscriptionOrder::create([
            'user_id' => $demo2->id,
            'tier_id' => $starterTier?->id ?? 1,
            'amount' => $starterTier?->price_monthly ?? 29.99,
            'currency' => 'USD',
            'billing_cycle' => 'monthly',
            'shipping_address' => ['full_name' => 'Sara Al-Demo', 'street' => '456 Olaya Street', 'city' => 'Riyadh', 'state' => '', 'zip' => '54321', 'country' => 'Saudi Arabia'],
            'shipping_status' => 'shipped',
            'tracking_number' => 'DMO-TRK-002',
            'shipped_at' => now()->subDays(2),
            'payment_method' => 'bank_transfer',
            'payment_status' => 'paid',
            'payment_reference' => 'BT-DEMO2-' . strtoupper(uniqid()),
            'notes' => 'First subscription - welcome kit',
        ]);

        Banner::create([
            'type' => 'announcement',
            'icon' => 'campaign',
            'color_scheme' => 'brand',
            'translations' => [
                'en' => ['title' => 'Welcome to Oasis Trace Demo!', 'description' => 'This is a demo environment. Explore all features including animal tracking, auctions, transfers, and more.'],
                'ar' => ['title' => 'مرحباً بك في Oasis Trace التجريبي!', 'description' => 'هذه بيئة تجريبية. استكشف جميع الميزات بما في ذلك تتبع الحيوانات والمزادات والتحويلات والمزيد.'],
            ],
            'sort_order' => 10,
            'is_active' => true,
            'expires_at' => now()->addDays(30),
        ]);

        Setting::updateOrCreate(
            ['key' => 'auction_auto_approve'],
            ['value' => '0']
        );
        Setting::updateOrCreate(
            ['key' => 'transfer_commission_enabled'],
            ['value' => '1']
        );
        Setting::updateOrCreate(
            ['key' => 'transfer_commission_type'],
            ['value' => 'percentage']
        );
        Setting::updateOrCreate(
            ['key' => 'transfer_commission_percentage'],
            ['value' => '5']
        );

        $this->command->info('Demo data created:');
        $this->command->info('  2 demo owners (Ahmed + Sara)');
        $this->command->info('  3 staff users (Support, Accountant, Customer Service)');
        $this->command->info('  2 shepherds');
        $this->command->info('  5 animals with devices');
        $this->command->info('  3 animal groups');
        $this->command->info('  2 geofences');
        $this->command->info('  7 medical records');
        $this->command->info('  6 vaccinations');
        $this->command->info('  5 tasks');
        $this->command->info('  3 auctions');
        $this->command->info('  1 conversation (4 messages)');
        $this->command->info('  1 completed transfer');
        $this->command->info('  2 subscriptions + 2 orders');
        $this->command->info('  1 banner');
        $this->command->info('=== DemoDataSeeder complete ===');
    }
}
