<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Recreate the Spatie permission pivot tables with UUID morph keys
     * to match the UUID primary keys on the users table.
     */
    public function up(): void
    {
        Schema::table('model_has_permissions', function (Blueprint $table): void {
            $table->dropPrimary('model_has_permissions_permission_model_type_primary');
            $table->dropIndex('model_has_permissions_model_id_model_type_index');
            $table->dropColumn('model_id');
        });

        Schema::table('model_has_permissions', function (Blueprint $table): void {
            $table->uuid('model_id');
            $table->index(['model_id', 'model_type'], 'model_has_permissions_model_id_model_type_index');
            $table->primary(['permission_id', 'model_id', 'model_type'], 'model_has_permissions_permission_model_type_primary');
        });

        Schema::table('model_has_roles', function (Blueprint $table): void {
            $table->dropPrimary('model_has_roles_role_model_type_primary');
            $table->dropIndex('model_has_roles_model_id_model_type_index');
            $table->dropColumn('model_id');
        });

        Schema::table('model_has_roles', function (Blueprint $table): void {
            $table->uuid('model_id');
            $table->index(['model_id', 'model_type'], 'model_has_roles_model_id_model_type_index');
            $table->primary(['role_id', 'model_id', 'model_type'], 'model_has_roles_role_model_type_primary');
        });
    }

    public function down(): void
    {
        Schema::table('model_has_permissions', function (Blueprint $table): void {
            $table->dropPrimary('model_has_permissions_permission_model_type_primary');
            $table->dropIndex('model_has_permissions_model_id_model_type_index');
            $table->dropColumn('model_id');
        });

        Schema::table('model_has_permissions', function (Blueprint $table): void {
            $table->unsignedBigInteger('model_id');
            $table->index(['model_id', 'model_type'], 'model_has_permissions_model_id_model_type_index');
            $table->primary(['permission_id', 'model_id', 'model_type'], 'model_has_permissions_permission_model_type_primary');
        });

        Schema::table('model_has_roles', function (Blueprint $table): void {
            $table->dropPrimary('model_has_roles_role_model_type_primary');
            $table->dropIndex('model_has_roles_model_id_model_type_index');
            $table->dropColumn('model_id');
        });

        Schema::table('model_has_roles', function (Blueprint $table): void {
            $table->unsignedBigInteger('model_id');
            $table->index(['model_id', 'model_type'], 'model_has_roles_model_id_model_type_index');
            $table->primary(['role_id', 'model_id', 'model_type'], 'model_has_roles_role_model_type_primary');
        });
    }
};
