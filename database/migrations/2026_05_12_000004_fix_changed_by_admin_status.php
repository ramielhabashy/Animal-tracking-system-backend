<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = 'NOW()';

        DB::statement("UPDATE user_subscriptions SET status = 'cancelled', cancelled_at = COALESCE(cancelled_at, updated_at, {$now}) WHERE status = 'changed_by_admin'");
    }

    public function down(): void
    {
        // Irreversible - old status value was meaningless
    }
};
