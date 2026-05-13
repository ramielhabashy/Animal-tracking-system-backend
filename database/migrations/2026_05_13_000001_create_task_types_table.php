<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('icon')->default('assignment');
            $table->string('color')->default('#002819');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        DB::table('task_types')->insert([
            ['name' => 'Inspection', 'slug' => 'inspection', 'icon' => 'search', 'color' => '#3B82F6'],
            ['name' => 'Medical', 'slug' => 'medical', 'icon' => 'medical_services', 'color' => '#EF4444'],
            ['name' => 'Feeding', 'slug' => 'feeding', 'icon' => 'restaurant', 'color' => '#10B981'],
            ['name' => 'Movement', 'slug' => 'movement', 'icon' => 'directions_walk', 'color' => '#F59E0B'],
            ['name' => 'Other', 'slug' => 'other', 'icon' => 'assignment', 'color' => '#717973'],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('task_types');
    }
};
