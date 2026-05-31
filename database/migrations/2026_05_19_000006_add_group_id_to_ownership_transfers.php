<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ownership_transfers', function (Blueprint $table) {
            $table->foreignId('group_id')->nullable()->constrained('animal_groups')->nullOnDelete()->after('reference_id');
            $table->index('group_id');
            $table->index('transfer_type');
        });
    }

    public function down(): void
    {
        Schema::table('ownership_transfers', function (Blueprint $table) {
            $table->dropForeign(['group_id']);
            $table->dropIndex(['group_id']);
            $table->dropIndex(['transfer_type']);
            $table->dropColumn('group_id');
        });
    }
};
