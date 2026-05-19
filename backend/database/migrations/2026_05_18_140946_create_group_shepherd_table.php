<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('group_shepherd', function (Blueprint $table) {
            $table->id();
            $table->foreignId('animal_group_id')->constrained('animal_groups')->cascadeOnDelete();
            $table->foreignId('shepherd_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['animal_group_id', 'shepherd_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('group_shepherd');
    }
};
