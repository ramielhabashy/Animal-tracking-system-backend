<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ownership_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('animal_id')->constrained();
            $table->foreignId('from_user_id')->nullable()->constrained('users');
            $table->foreignId('to_user_id')->constrained('users');
            $table->foreignId('transfer_id')->nullable()->constrained('ownership_transfers');
            $table->string('transfer_type');
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->decimal('commission_amount', 12, 2)->default(0);
            $table->decimal('agreed_price', 12, 2)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at');

            $table->index('animal_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ownership_history');
    }
};
