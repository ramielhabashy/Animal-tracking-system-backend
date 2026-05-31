<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('geofence_alerts', function (Blueprint $table) {
            $table->string('type', 20)->change();
            $table->foreignId('geofence_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('geofence_alerts', function (Blueprint $table) {
            $table->string('type', 10)->change();
        });
    }
};
