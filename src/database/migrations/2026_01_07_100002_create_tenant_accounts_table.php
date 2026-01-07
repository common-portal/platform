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
        // Create enum type
        DB::statement("CREATE TYPE account_type_enum AS ENUM ('personal_individual', 'business_organization')");

        Schema::create('tenant_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('account_display_name', 255);
            $table->string('whitelabel_subdomain_slug', 100)->nullable()->unique();
            $table->string('branding_logo_image_path', 500)->nullable();
            $table->string('primary_contact_full_name', 255)->nullable();
            $table->string('primary_contact_email_address', 255)->nullable();
            $table->boolean('is_soft_deleted')->default(false);
            $table->timestamp('soft_deleted_at_timestamp')->nullable();
            $table->timestamp('created_at_timestamp')->useCurrent();
            $table->timestamp('updated_at_timestamp')->useCurrent();
        });

        // Add enum column separately for PostgreSQL
        DB::statement("ALTER TABLE tenant_accounts ADD COLUMN account_type account_type_enum NOT NULL DEFAULT 'personal_individual'");

        // Partial indexes
        DB::statement('CREATE INDEX idx_tenant_accounts_subdomain ON tenant_accounts(whitelabel_subdomain_slug) WHERE whitelabel_subdomain_slug IS NOT NULL');
        DB::statement('CREATE INDEX idx_tenant_accounts_not_deleted ON tenant_accounts(is_soft_deleted) WHERE is_soft_deleted = FALSE');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_accounts');
        DB::statement('DROP TYPE IF EXISTS account_type_enum');
    }
};
