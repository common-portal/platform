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
        Schema::table('team_membership_invitations', function (Blueprint $table) {
            $table->jsonb('invited_permission_slugs')->default('[]')->after('invited_by_member_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('team_membership_invitations', function (Blueprint $table) {
            $table->dropColumn('invited_permission_slugs');
        });
    }
};
