<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = 'NOW()';

        DB::statement("
            INSERT INTO user_subscriptions (user_id, tier_id, status, started_at, billing_cycle, created_at, updated_at)
            SELECT u.id, u.subscription_tier_id, 'active', u.created_at, 'monthly', {$now}, {$now}
            FROM users u
            INNER JOIN model_has_roles mhr ON mhr.model_id = u.id AND mhr.model_type = 'App\\\\Models\\\\User'
            INNER JOIN roles r ON r.id = mhr.role_id AND r.name = 'Owner'
            WHERE u.subscription_tier_id IS NOT NULL
            AND NOT EXISTS (
                SELECT 1 FROM user_subscriptions us WHERE us.user_id = u.id
            )
        ");
    }

    public function down(): void
    {
        DB::statement("
            DELETE FROM user_subscriptions
            WHERE id IN (
                SELECT us.id FROM (
                    SELECT us2.id FROM user_subscriptions us2
                    INNER JOIN users u ON u.id = us2.user_id
                    WHERE us2.created_at = u.created_at
                ) AS tmp
            )
        ");
    }
};
