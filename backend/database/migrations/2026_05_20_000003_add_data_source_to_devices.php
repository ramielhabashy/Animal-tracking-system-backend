<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->string('data_source', 20)->default('simulated')->after('user_subscription_id');
            $table->string('driver', 50)->nullable()->after('data_source');
        });

        Schema::table('location_history', function (Blueprint $table) {
            $table->string('data_source', 20)->default('simulated')->after('heading');
        });
    }

    public function down(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->dropColumn(['data_source', 'driver']);
        });

        Schema::table('location_history', function (Blueprint $table) {
            $table->dropColumn('data_source');
        });
    }
};
