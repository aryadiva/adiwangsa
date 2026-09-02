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
        Schema::table('workers', function (Blueprint $table) {
            $table->date('active_start_date')->nullable()->after('is_active');
            $table->date('deactivation_date')->nullable()->after('active_start_date');
            $table->string('phone_number', 32)->nullable()->after('deactivation_date');
            $table->string('bank_account_number', 64)->nullable()->after('phone_number');
            $table->string('bank_account_name')->nullable()->after('bank_account_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('workers', function (Blueprint $table) {
            $table->dropColumn([
                'active_start_date',
                'deactivation_date',
                'phone_number',
                'bank_account_number',
                'bank_account_name',
            ]);
        });
    }
};
