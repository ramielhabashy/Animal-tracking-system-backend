<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ownership_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('from_user_id')->constrained('users');
            $table->foreignId('to_user_id')->constrained('users');
            $table->string('status')->default('pending');
            $table->string('transfer_type')->default('manual');
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->decimal('agreed_price', 12, 2)->nullable();
            $table->decimal('commission_percentage', 5, 2)->default(0);
            $table->decimal('commission_amount', 12, 2)->default(0);
            $table->boolean('commission_paid')->default(false);
            $table->text('notes')->nullable();
            $table->string('rejection_reason')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index(['from_user_id', 'status']);
            $table->index(['to_user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ownership_transfers');
    }
};
