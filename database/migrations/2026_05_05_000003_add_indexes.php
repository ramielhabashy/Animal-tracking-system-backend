<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add database indexes to improve query performance.
     * 
     * These indexes target frequently queried columns and foreign keys
     * to speed up lookups and joins.
     */
    public function up(): void
    {
        // Users table indexes
        Schema::table('users', function (Blueprint $table) {
            if (!$this->hasIndex('users', 'users_subscription_tier_id_index')) {
                $table->index('subscription_tier_id', 'users_subscription_tier_id_index');
            }
            if (!$this->hasIndex('users', 'users_managed_by_index')) {
                $table->index('managed_by', 'users_managed_by_index');
            }
            if (!$this->hasIndex('users', 'users_is_active_index')) {
                $table->index('is_active', 'users_is_active_index');
            }
            if (!$this->hasIndex('users', 'users_language_index')) {
                $table->index('language', 'users_language_index');
            }
        });

        // Animals table indexes
        Schema::table('animals', function (Blueprint $table) {
            if (!$this->hasIndex('animals', 'animals_owner_id_index')) {
                $table->index('owner_id', 'animals_owner_id_index');
            }
            if (!$this->hasIndex('animals', 'animals_species_index')) {
                $table->index('species', 'animals_species_index');
            }
            if (!$this->hasIndex('animals', 'animals_breed_index')) {
                $table->index('breed', 'animals_breed_index');
            }
        });

        // Devices table indexes
        Schema::table('devices', function (Blueprint $table) {
            if (!$this->hasIndex('devices', 'devices_owner_id_index')) {
                $table->index('owner_id', 'devices_owner_id_index');
            }
            if (!$this->hasIndex('devices', 'devices_animal_id_index')) {
                $table->index('animal_id', 'devices_animal_id_index');
            }
            if (!$this->hasIndex('devices', 'devices_status_index')) {
                $table->index('status', 'devices_status_index');
            }
        });

        // Geofence alerts indexes
        Schema::table('geofence_alerts', function (Blueprint $table) {
            if (!$this->hasIndex('geofence_alerts', 'geofence_alerts_geofence_id_index')) {
                $table->index('geofence_id', 'geofence_alerts_geofence_id_index');
            }
            if (!$this->hasIndex('geofence_alerts', 'geofence_alerts_animal_id_index')) {
                $table->index('animal_id', 'geofence_alerts_animal_id_index');
            }
            if (!$this->hasIndex('geofence_alerts', 'geofence_alerts_triggered_at_index')) {
                $table->index('triggered_at', 'geofence_alerts_triggered_at_index');
            }
            if (!$this->hasIndex('geofence_alerts', 'geofence_alerts_type_index')) {
                $table->index('type', 'geofence_alerts_type_index');
            }
        });

        // Auctions indexes
        Schema::table('auctions', function (Blueprint $table) {
            if (!$this->hasIndex('auctions', 'auctions_animal_id_index')) {
                $table->index('animal_id', 'auctions_animal_id_index');
            }
            if (!$this->hasIndex('auctions', 'auctions_owner_id_index')) {
                $table->index('owner_id', 'auctions_owner_id_index');
            }
            if (!$this->hasIndex('auctions', 'auctions_status_index')) {
                $table->index('status', 'auctions_status_index');
            }
            if (!$this->hasIndex('auctions', 'auctions_ends_at_index')) {
                $table->index('ends_at', 'auctions_ends_at_index');
            }
            if (!$this->hasIndex('auctions', 'auctions_winner_id_index')) {
                $table->index('winner_id', 'auctions_winner_id_index');
            }
        });

        // Bids indexes
        Schema::table('bids', function (Blueprint $table) {
            if (!$this->hasIndex('bids', 'bids_auction_id_index')) {
                $table->index('auction_id', 'bids_auction_id_index');
            }
            if (!$this->hasIndex('bids', 'bids_user_id_index')) {
                $table->index('user_id', 'bids_user_id_index');
            }
            if (!$this->hasIndex('bids', 'bids_amount_index')) {
                $table->index('amount', 'bids_amount_index');
            }
        });

        // Medical records indexes
        Schema::table('medical_records', function (Blueprint $table) {
            if (!$this->hasIndex('medical_records', 'medical_records_animal_id_index')) {
                $table->index('animal_id', 'medical_records_animal_id_index');
            }
            if (!$this->hasIndex('medical_records', 'medical_records_owner_id_index')) {
                $table->index('owner_id', 'medical_records_owner_id_index');
            }
            if (!$this->hasIndex('medical_records', 'medical_records_record_date_index')) {
                $table->index('record_date', 'medical_records_record_date_index');
            }
        });
    }

    public function down(): void
    {
        // Drop indexes in reverse order
        Schema::table('medical_records', function (Blueprint $table) {
            if ($this->hasIndex('medical_records', 'medical_records_record_date_index')) {
                $table->dropIndex('medical_records_record_date_index');
            }
            if ($this->hasIndex('medical_records', 'medical_records_owner_id_index')) {
                $table->dropIndex('medical_records_owner_id_index');
            }
            if ($this->hasIndex('medical_records', 'medical_records_animal_id_index')) {
                $table->dropIndex('medical_records_animal_id_index');
            }
        });

        Schema::table('bids', function (Blueprint $table) {
            if ($this->hasIndex('bids', 'bids_amount_index')) {
                $table->dropIndex('bids_amount_index');
            }
            if ($this->hasIndex('bids', 'bids_user_id_index')) {
                $table->dropIndex('bids_user_id_index');
            }
            if ($this->hasIndex('bids', 'bids_auction_id_index')) {
                $table->dropIndex('bids_auction_id_index');
            }
        });

        Schema::table('auctions', function (Blueprint $table) {
            if ($this->hasIndex('auctions', 'auctions_winner_id_index')) {
                $table->dropIndex('auctions_winner_id_index');
            }
            if ($this->hasIndex('auctions', 'auctions_ends_at_index')) {
                $table->dropIndex('auctions_ends_at_index');
            }
            if ($this->hasIndex('auctions', 'auctions_status_index')) {
                $table->dropIndex('auctions_status_index');
            }
            if ($this->hasIndex('auctions', 'auctions_owner_id_index')) {
                $table->dropIndex('auctions_owner_id_index');
            }
            if ($this->hasIndex('auctions', 'auctions_animal_id_index')) {
                $table->dropIndex('auctions_animal_id_index');
            }
        });

        Schema::table('geofence_alerts', function (Blueprint $table) {
            if ($this->hasIndex('geofence_alerts', 'geofence_alerts_type_index')) {
                $table->dropIndex('geofence_alerts_type_index');
            }
            if ($this->hasIndex('geofence_alerts', 'geofence_alerts_triggered_at_index')) {
                $table->dropIndex('geofence_alerts_triggered_at_index');
            }
            if ($this->hasIndex('geofence_alerts', 'geofence_alerts_animal_id_index')) {
                $table->dropIndex('geofence_alerts_animal_id_index');
            }
            if ($this->hasIndex('geofence_alerts', 'geofence_alerts_geofence_id_index')) {
                $table->dropIndex('geofence_alerts_geofence_id_index');
            }
        });

        Schema::table('devices', function (Blueprint $table) {
            if ($this->hasIndex('devices', 'devices_status_index')) {
                $table->dropIndex('devices_status_index');
            }
            if ($this->hasIndex('devices', 'devices_animal_id_index')) {
                $table->dropIndex('devices_animal_id_index');
            }
            if ($this->hasIndex('devices', 'devices_owner_id_index')) {
                $table->dropIndex('devices_owner_id_index');
            }
        });

        Schema::table('animals', function (Blueprint $table) {
            if ($this->hasIndex('animals', 'animals_breed_index')) {
                $table->dropIndex('animals_breed_index');
            }
            if ($this->hasIndex('animals', 'animals_species_index')) {
                $table->dropIndex('animals_species_index');
            }
            if ($this->hasIndex('animals', 'animals_owner_id_index')) {
                $table->dropIndex('animals_owner_id_index');
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if ($this->hasIndex('users', 'users_language_index')) {
                $table->dropIndex('users_language_index');
            }
            if ($this->hasIndex('users', 'users_is_active_index')) {
                $table->dropIndex('users_is_active_index');
            }
            if ($this->hasIndex('users', 'users_managed_by_index')) {
                $table->dropIndex('users_managed_by_index');
            }
            if ($this->hasIndex('users', 'users_subscription_tier_id_index')) {
                $table->dropIndex('users_subscription_tier_id_index');
            }
        });
    }

    /**
     * Check if an index exists on a table.
     */
    private function hasIndex(string $table, string $indexName): bool
    {
        $connection = Schema::getConnection();
        $driverName = $connection->getDriverName();
        
        if ($driverName === 'sqlite') {
            // SQLite: check indexes using PRAGMA
            $indexes = $connection->select("PRAGMA index_list(" . $table . ")");
            foreach ($indexes as $index) {
                if ($index->name === $indexName) {
                    return true;
                }
            }
            return false;
        } else {
            // MySQL/Others: use information_schema
            $databaseName = $connection->getDatabaseName();
            
            $result = $connection->select("
                SELECT COUNT(*) as count 
                FROM information_schema.STATISTICS 
                WHERE TABLE_SCHEMA = ? 
                AND TABLE_NAME = ? 
                AND INDEX_NAME = ?
            ", [$databaseName, $table, $indexName]);
            
            return $result[0]->count > 0;
        }
    }
};
