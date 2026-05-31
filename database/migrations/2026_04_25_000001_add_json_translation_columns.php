<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Species table - add JSON columns
        Schema::table('species', function (Blueprint $table) {
            if (!Schema::hasColumn('species', 'name_json')) {
                $table->json('name_json')->nullable()->after('name');
            }
            if (!Schema::hasColumn('species', 'description_json')) {
                $table->json('description_json')->nullable()->after('description');
            }
        });

        // Breeds table
        Schema::table('breeds', function (Blueprint $table) {
            if (!Schema::hasColumn('breeds', 'name_json')) {
                $table->json('name_json')->nullable()->after('name');
            }
            if (!Schema::hasColumn('breeds', 'description_json')) {
                $table->json('description_json')->nullable()->after('description');
            }
        });

        // Geofences table
        Schema::table('geofences', function (Blueprint $table) {
            if (!Schema::hasColumn('geofences', 'name_json')) {
                $table->json('name_json')->nullable()->after('name');
            }
        });

        // Animal groups table
        Schema::table('animal_groups', function (Blueprint $table) {
            if (!Schema::hasColumn('animal_groups', 'name_json')) {
                $table->json('name_json')->nullable()->after('name');
            }
            if (!Schema::hasColumn('animal_groups', 'description_json')) {
                $table->json('description_json')->nullable()->after('description');
            }
        });

        // Devices table
        Schema::table('devices', function (Blueprint $table) {
            if (!Schema::hasColumn('devices', 'name_json')) {
                $table->json('name_json')->nullable()->after('name');
            }
        });

        // Subscription tiers table
        Schema::table('subscription_tiers', function (Blueprint $table) {
            if (!Schema::hasColumn('subscription_tiers', 'name_json')) {
                $table->json('name_json')->nullable()->after('name');
            }
            if (!Schema::hasColumn('subscription_tiers', 'description_json')) {
                $table->json('description_json')->nullable()->after('description');
            }
        });

        // Tasks table
        Schema::table('tasks', function (Blueprint $table) {
            if (!Schema::hasColumn('tasks', 'title_json')) {
                $table->json('title_json')->nullable()->after('title');
            }
            if (!Schema::hasColumn('tasks', 'description_json')) {
                $table->json('description_json')->nullable()->after('description');
            }
        });

        // Predefined tasks table
        Schema::table('predefined_tasks', function (Blueprint $table) {
            if (!Schema::hasColumn('predefined_tasks', 'title_json')) {
                $table->json('title_json')->nullable()->after('title');
            }
            if (!Schema::hasColumn('predefined_tasks', 'description_json')) {
                $table->json('description_json')->nullable()->after('description');
            }
        });

        // Auctions table
        Schema::table('auctions', function (Blueprint $table) {
            if (!Schema::hasColumn('auctions', 'title_json')) {
                $table->json('title_json')->nullable()->after('title');
            }
            if (!Schema::hasColumn('auctions', 'description_json')) {
                $table->json('description_json')->nullable()->after('description');
            }
        });

        // Medical records table
        Schema::table('medical_records', function (Blueprint $table) {
            if (!Schema::hasColumn('medical_records', 'title_json')) {
                $table->json('title_json')->nullable()->after('title');
            }
            if (!Schema::hasColumn('medical_records', 'description_json')) {
                $table->json('description_json')->nullable()->after('description');
            }
            if (!Schema::hasColumn('medical_records', 'notes_json')) {
                $table->json('notes_json')->nullable()->after('notes');
            }
        });

        // Vaccination schedules table
        Schema::table('vaccination_schedules', function (Blueprint $table) {
            if (!Schema::hasColumn('vaccination_schedules', 'vaccine_name_json')) {
                $table->json('vaccine_name_json')->nullable()->after('vaccine_name');
            }
            if (!Schema::hasColumn('vaccination_schedules', 'vaccination_type_json')) {
                $table->json('vaccination_type_json')->nullable()->after('vaccination_type');
            }
            if (!Schema::hasColumn('vaccination_schedules', 'notes_json')) {
                $table->json('notes_json')->nullable()->after('notes');
            }
            if (!Schema::hasColumn('vaccination_schedules', 'veterinarian_json')) {
                $table->json('veterinarian_json')->nullable()->after('veterinarian');
            }
            if (!Schema::hasColumn('vaccination_schedules', 'clinic_json')) {
                $table->json('clinic_json')->nullable()->after('clinic');
            }
        });
    }

    public function down(): void
    {
        Schema::table('species', function (Blueprint $table) {
            $table->dropColumn(['name_json', 'description_json']);
        });
        Schema::table('breeds', function (Blueprint $table) {
            $table->dropColumn(['name_json', 'description_json']);
        });
        Schema::table('geofences', function (Blueprint $table) {
            $table->dropColumn('name_json');
        });
        Schema::table('animal_groups', function (Blueprint $table) {
            $table->dropColumn(['name_json', 'description_json']);
        });
        Schema::table('devices', function (Blueprint $table) {
            $table->dropColumn('name_json');
        });
        Schema::table('subscription_tiers', function (Blueprint $table) {
            $table->dropColumn(['name_json', 'description_json']);
        });
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn(['title_json', 'description_json']);
        });
        Schema::table('predefined_tasks', function (Blueprint $table) {
            $table->dropColumn(['title_json', 'description_json']);
        });
        Schema::table('auctions', function (Blueprint $table) {
            $table->dropColumn(['title_json', 'description_json']);
        });
        Schema::table('medical_records', function (Blueprint $table) {
            $table->dropColumn(['title_json', 'description_json', 'notes_json']);
        });
        Schema::table('vaccination_schedules', function (Blueprint $table) {
            $table->dropColumn(['vaccine_name_json', 'vaccination_type_json', 'notes_json', 'veterinarian_json', 'clinic_json']);
        });
    }
};