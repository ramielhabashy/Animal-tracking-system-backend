<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE `animals` MODIFY COLUMN `species` ENUM('Camel', 'Goat', 'Sheep', 'Cattle', 'Horse') NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE `animals` MODIFY COLUMN `species` ENUM('Camel', 'Goat', 'Sheep') NOT NULL");
    }
};
