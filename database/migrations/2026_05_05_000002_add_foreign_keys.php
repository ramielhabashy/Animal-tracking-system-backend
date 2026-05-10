<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add missing foreign key constraints to maintain referential integrity.
     * 
     * - users.subscription_tier_id -> subscription_tiers.id
     * - users.managed_by -> users.id (self-referencing)
     * - auctions.verified_by -> users.id
     * - auctions.second_winner_id -> users.id
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Add foreign key for subscription_tier_id if it doesn't exist
            if (Schema::hasColumn('users', 'subscription_tier_id') && !$this->hasForeignKey('users', 'subscription_tier_id')) {
                $table->foreign('subscription_tier_id')->references('id')->on('subscription_tiers')->nullOnDelete();
            }
            
            // Add self-referencing foreign key for managed_by if it doesn't exist
            if (Schema::hasColumn('users', 'managed_by') && !$this->hasForeignKey('users', 'managed_by')) {
                $table->foreign('managed_by')->references('id')->on('users')->nullOnDelete();
            }
        });

        Schema::table('auctions', function (Blueprint $table) {
            // Add foreign key for verified_by if it doesn't exist
            if (Schema::hasColumn('auctions', 'verified_by') && !$this->hasForeignKey('auctions', 'verified_by')) {
                $table->foreign('verified_by')->references('id')->on('users')->nullOnDelete();
            }
            
            // Add foreign key for second_winner_id if it doesn't exist
            if (Schema::hasColumn('auctions', 'second_winner_id') && !$this->hasForeignKey('auctions', 'second_winner_id')) {
                $table->foreign('second_winner_id')->references('id')->on('users')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('auctions', function (Blueprint $table) {
            if ($this->hasForeignKey('auctions', 'second_winner_id')) {
                $table->dropForeign(['second_winner_id']);
            }
            if ($this->hasForeignKey('auctions', 'verified_by')) {
                $table->dropForeign(['verified_by']);
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if ($this->hasForeignKey('users', 'managed_by')) {
                $table->dropForeign(['managed_by']);
            }
            if ($this->hasForeignKey('users', 'subscription_tier_id')) {
                $table->dropForeign(['subscription_tier_id']);
            }
        });
    }

    /**
     * Check if a foreign key exists on a table for a given column.
     */
    private function hasForeignKey(string $table, string $column): bool
    {
        $connection = Schema::getConnection();
        $driverName = $connection->getDriverName();
        
        if ($driverName === 'sqlite') {
            // SQLite: check foreign keys using PRAGMA
            $keyName = "{$table}_{$column}_foreign";
            $foreignKeys = $connection->select("PRAGMA foreign_key_list({$table})");
            foreach ($foreignKeys as $fk) {
                if ($fk->from === $column) {
                    return true;
                }
            }
            return false;
        } else {
            // MySQL/Others: use information_schema
            $databaseName = $connection->getDatabaseName();
            $keyName = "{$table}_{$column}_foreign";
            
            $result = $connection->select("
                SELECT COUNT(*) as count 
                FROM information_schema.TABLE_CONSTRAINTS 
                WHERE CONSTRAINT_SCHEMA = ? 
                AND TABLE_NAME = ? 
                AND CONSTRAINT_NAME = ?
                AND CONSTRAINT_TYPE = 'FOREIGN KEY'
            ", [$databaseName, $table, $keyName]);
            
            return $result[0]->count > 0;
        }
    }
};
