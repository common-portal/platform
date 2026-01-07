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
        Schema::create('external_service_api_credentials', function (Blueprint $table) {
            $table->id();
            $table->string('external_service_name', 100);
            $table->text('encrypted_api_key');
            $table->boolean('is_currently_active_service')->default(false);
            $table->timestamp('created_at_timestamp')->useCurrent();
            $table->timestamp('updated_at_timestamp')->useCurrent();
        });

        // Partial index for active services
        DB::statement('CREATE INDEX idx_api_credentials_active ON external_service_api_credentials(is_currently_active_service) WHERE is_currently_active_service = TRUE');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('external_service_api_credentials');
    }
};
