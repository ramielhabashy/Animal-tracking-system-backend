<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('translation_cache', function (Blueprint $table) {
            $table->id();
            $table->string('source_text_hash', 64)->index();
            $table->string('source_lang', 10);
            $table->string('target_lang', 10);
            $table->text('translated_text');
            $table->timestamp('expires_at')->index();
            $table->timestamps();

            $table->unique(['source_text_hash', 'source_lang', 'target_lang'], 'translation_cache_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('translation_cache');
    }
};
