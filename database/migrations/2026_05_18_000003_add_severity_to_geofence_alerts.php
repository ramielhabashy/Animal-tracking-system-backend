<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('geofence_alerts', function (Blueprint $table) {
            $table->string('severity', 20)->nullable()->after('type');
            $table->text('message')->nullable()->after('severity');
            $table->boolean('resolved')->default(false)->after('message');
        });
    }

    public function down(): void
    {
        Schema::table('geofence_alerts', function (Blueprint $table) {
            $table->dropColumn(['severity', 'message', 'resolved']);
        });
    }
};
