<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_log_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('icon')->default('note');
            $table->string('color')->default('#717973');
            $table->boolean('allows_media')->default(false);
            $table->boolean('is_status')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        DB::table('task_log_types')->insert([
            ['name' => 'Done', 'slug' => 'done', 'icon' => 'check_circle', 'color' => '#10B981', 'allows_media' => true, 'is_status' => true],
            ['name' => 'Blocked', 'slug' => 'blocked', 'icon' => 'block', 'color' => '#BA1A1A', 'allows_media' => true, 'is_status' => true],
            ['name' => 'Rescheduled', 'slug' => 'rescheduled', 'icon' => 'schedule', 'color' => '#F59E0B', 'allows_media' => true, 'is_status' => true],
            ['name' => 'In Progress', 'slug' => 'in_progress', 'icon' => 'play_arrow', 'color' => '#3B82F6', 'allows_media' => false, 'is_status' => true],
            ['name' => 'Checkpoint', 'slug' => 'checkpoint', 'icon' => 'location_on', 'color' => '#06402B', 'allows_media' => true, 'is_status' => false],
            ['name' => 'Note', 'slug' => 'note', 'icon' => 'note', 'color' => '#717973', 'allows_media' => false, 'is_status' => false],
            ['name' => 'Photo', 'slug' => 'photo', 'icon' => 'photo_camera', 'color' => '#8B5CF6', 'allows_media' => true, 'is_status' => false],
            ['name' => 'Location Update', 'slug' => 'location_update', 'icon' => 'my_location', 'color' => '#06402B', 'allows_media' => false, 'is_status' => false],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('task_log_types');
    }
};
