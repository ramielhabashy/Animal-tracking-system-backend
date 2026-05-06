<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Add payment-related fields to the auctions table.
     * These fields support the payment verification workflow after an auction ends.
     */
    public function up(): void
    {
        Schema::table('auctions', function (Blueprint $table) {
            // Add payment fields if they don't exist
            if (!Schema::hasColumn('auctions', 'payment_proof_url')) {
                $table->string('payment_proof_url')->nullable()->after('winner_id');
            }
            if (!Schema::hasColumn('auctions', 'payment_expires_at')) {
                $table->timestamp('payment_expires_at')->nullable()->after('payment_proof_url');
            }
            if (!Schema::hasColumn('auctions', 'payment_verified_at')) {
                $table->timestamp('payment_verified_at')->nullable()->after('payment_expires_at');
            }
            if (!Schema::hasColumn('auctions', 'payment_status')) {
                $table->string('payment_status', 20)->default('pending')->after('payment_verified_at');
            }
            if (!Schema::hasColumn('auctions', 'payment_notes')) {
                $table->text('payment_notes')->nullable()->after('payment_status');
            }
            if (!Schema::hasColumn('auctions', 'verified_by')) {
                $table->unsignedBigInteger('verified_by')->nullable()->after('payment_notes');
            }
            if (!Schema::hasColumn('auctions', 'second_winner_id')) {
                $table->unsignedBigInteger('second_winner_id')->nullable()->after('verified_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('auctions', function (Blueprint $table) {
            $columns = [];
            if (Schema::hasColumn('auctions', 'payment_proof_url')) {
                $columns[] = 'payment_proof_url';
            }
            if (Schema::hasColumn('auctions', 'payment_expires_at')) {
                $columns[] = 'payment_expires_at';
            }
            if (Schema::hasColumn('auctions', 'payment_verified_at')) {
                $columns[] = 'payment_verified_at';
            }
            if (Schema::hasColumn('auctions', 'payment_status')) {
                $columns[] = 'payment_status';
            }
            if (Schema::hasColumn('auctions', 'payment_notes')) {
                $columns[] = 'payment_notes';
            }
            if (Schema::hasColumn('auctions', 'verified_by')) {
                $columns[] = 'verified_by';
            }
            if (Schema::hasColumn('auctions', 'second_winner_id')) {
                $columns[] = 'second_winner_id';
            }
            if (!empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};
