<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('support_ticket_attachments', function (Blueprint $table) {
            $table->string('original_filename', 500)->change();
            $table->string('stored_filename', 500)->change();
            $table->string('file_path', 600)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('support_ticket_attachments', function (Blueprint $table) {
            $table->string('original_filename', 255)->change();
            $table->string('stored_filename', 255)->change();
            $table->string('file_path', 255)->change();
        });
    }
};
