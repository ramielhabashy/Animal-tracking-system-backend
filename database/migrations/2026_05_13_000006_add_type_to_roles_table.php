<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->string('type', 20)->default('user')->after('guard_name');
        });

        DB::table('roles')->where('name', 'Admin')->update(['type' => 'admin']);
        DB::table('roles')->whereIn('name', ['Owner', 'Manager', 'Shepherd', 'Doctor'])->update(['type' => 'user']);
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
