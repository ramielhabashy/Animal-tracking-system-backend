<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vaccination_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('icon')->default('vaccines');
            $table->string('color')->default('#002819');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        DB::table('vaccination_types')->insert([
            ['name' => 'Routine', 'slug' => 'routine', 'icon' => 'vaccines', 'color' => '#10B981'],
            ['name' => 'Booster', 'slug' => 'booster', 'icon' => 'refresh', 'color' => '#3B82F6'],
            ['name' => 'Emergency', 'slug' => 'emergency', 'icon' => 'emergency', 'color' => '#EF4444'],
            ['name' => 'Seasonal', 'slug' => 'seasonal', 'icon' => 'ac_unit', 'color' => '#F59E0B'],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('vaccination_types');
    }
};
