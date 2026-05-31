<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_logs', function (Blueprint $table) {
            $table->id();
            $table->string('recipient');
            $table->string('subject')->nullable();
            $table->enum('status', ['sending', 'sent', 'failed'])->default('sending');
            $table->text('error_message')->nullable();
            $table->text('response')->nullable();
            $table->string('mailer')->default('smtp');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_logs');
    }
};
