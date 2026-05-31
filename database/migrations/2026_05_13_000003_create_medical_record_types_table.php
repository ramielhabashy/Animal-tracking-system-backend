<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medical_record_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('icon')->default('medical_services');
            $table->string('color')->default('#002819');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        DB::table('medical_record_types')->insert([
            ['name' => 'Vaccination', 'slug' => 'vaccination', 'icon' => 'vaccines', 'color' => '#059669'],
            ['name' => 'Checkup', 'slug' => 'checkup', 'icon' => 'medical_services', 'color' => '#3B82F6'],
            ['name' => 'Surgery', 'slug' => 'surgery', 'icon' => 'local_hospital', 'color' => '#7C3AED'],
            ['name' => 'Treatment', 'slug' => 'treatment', 'icon' => 'healing', 'color' => '#D97706'],
            ['name' => 'Emergency', 'slug' => 'emergency', 'icon' => 'emergency', 'color' => '#EF4444'],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('medical_record_types');
    }
};
