<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->timestamp('delivered_at')->nullable()->after('completed_at');
            $table->foreignId('delivered_by')->nullable()->constrained('users')->after('delivered_at');
            $table->text('deliver_notes')->nullable()->after('delivered_by');
            $table->text('reject_notes')->nullable()->after('deliver_notes');
        });

        DB::statement("ALTER TABLE tasks MODIFY COLUMN status ENUM('pending', 'in_progress', 'delivered', 'completed', 'cancelled') DEFAULT 'pending'");
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropForeign(['delivered_by']);
            $table->dropColumn(['delivered_at', 'delivered_by', 'deliver_notes', 'reject_notes']);
        });

        DB::statement("ALTER TABLE tasks MODIFY COLUMN status ENUM('pending', 'in_progress', 'completed', 'cancelled') DEFAULT 'pending'");
    }
};
