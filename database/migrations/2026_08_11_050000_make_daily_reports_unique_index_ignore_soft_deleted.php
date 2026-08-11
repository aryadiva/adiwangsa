<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('daily_reports', function (Blueprint $table) {
            $table->dropUnique('daily_reports_site_id_report_date_unique');
        });

        DB::statement(
            'CREATE UNIQUE INDEX daily_reports_site_id_report_date_active_unique '
            .'ON daily_reports (site_id, report_date) WHERE deleted_at IS NULL'
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS daily_reports_site_id_report_date_active_unique');

        Schema::table('daily_reports', function (Blueprint $table) {
            $table->unique(['site_id', 'report_date'], 'daily_reports_site_id_report_date_unique');
        });
    }
};
