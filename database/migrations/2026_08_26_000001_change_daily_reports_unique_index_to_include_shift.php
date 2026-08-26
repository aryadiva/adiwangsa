<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('DROP INDEX IF EXISTS daily_reports_site_id_report_date_active_unique');

        DB::statement(
            'CREATE UNIQUE INDEX daily_reports_site_date_shift_active_unique '
            .'ON daily_reports (site_id, report_date, shift) WHERE deleted_at IS NULL'
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS daily_reports_site_date_shift_active_unique');

        DB::statement(
            'CREATE UNIQUE INDEX daily_reports_site_id_report_date_active_unique '
            .'ON daily_reports (site_id, report_date) WHERE deleted_at IS NULL'
        );
    }
};
