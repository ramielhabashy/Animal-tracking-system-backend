<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tier_id')->constrained('subscription_tiers')->cascadeOnDelete();
            $table->foreignId('user_subscription_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('USD');
            $table->string('billing_cycle', 10)->default('monthly');
            $table->json('shipping_address')->nullable();
            $table->string('shipping_status', 20)->default('pending');
            $table->string('tracking_number')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->string('payment_method', 30)->nullable();
            $table->string('payment_status', 20)->default('pending');
            $table->string('stripe_session_id')->nullable();
            $table->string('payment_intent_id')->nullable();
            $table->string('payment_reference')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('payment_status');
            $table->index('shipping_status');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_orders');
    }
};
