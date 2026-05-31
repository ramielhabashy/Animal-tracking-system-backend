<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ownership_transfer_animals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ownership_transfer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('animal_id')->constrained();
            $table->timestamps();

            $table->unique(['ownership_transfer_id', 'animal_id'], 'transfer_animal_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ownership_transfer_animals');
    }
};
