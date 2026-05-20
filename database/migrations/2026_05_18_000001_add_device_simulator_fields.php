<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->decimal('temperature', 5, 2)->nullable()->after('signal_strength');
            $table->decimal('speed', 6, 2)->nullable()->after('temperature');
            $table->boolean('is_lost')->default(false)->after('speed');
            $table->timestamp('last_temperature_update')->nullable()->after('last_ping');
        });
    }

    public function down(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->dropColumn(['temperature', 'speed', 'is_lost', 'last_temperature_update']);
        });
    }
};
