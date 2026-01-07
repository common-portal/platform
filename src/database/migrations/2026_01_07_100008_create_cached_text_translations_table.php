<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('cached_text_translations', function (Blueprint $table) {
            $table->id();
            $table->string('translation_hash', 64)->unique();
            $table->text('original_english_text');
            $table->string('target_language_iso3', 3);
            $table->text('translated_text');
            $table->timestamp('created_at_timestamp')->useCurrent();

            $table->index('target_language_iso3', 'idx_translations_lookup');
        });

        // Unique index on text hash + language
        DB::statement('CREATE UNIQUE INDEX idx_translations_unique ON cached_text_translations(md5(original_english_text), target_language_iso3)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cached_text_translations');
    }
};
