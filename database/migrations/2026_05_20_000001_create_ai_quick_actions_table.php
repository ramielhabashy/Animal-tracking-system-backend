<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_quick_actions', function (Blueprint $table) {
            $table->id();
            $table->string('role')->nullable()->index();
            $table->enum('type', ['quick_action', 'suggestion']);
            $table->string('icon')->nullable();
            $table->string('label');
            $table->text('prompt');
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_quick_actions');
    }
};
