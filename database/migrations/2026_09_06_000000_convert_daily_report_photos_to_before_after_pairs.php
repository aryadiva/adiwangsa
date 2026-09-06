<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // v0.1.0 shipped a free multi-upload list; collapse any duplicate
        // rows per report (keeping the newest) so the one-pair-per-report
        // unique index below can be created on existing data.
        DB::statement(<<<'SQL'
            UPDATE daily_report_photos
            SET deleted_at = NOW()
            WHERE deleted_at IS NULL
              AND id NOT IN (
                  SELECT DISTINCT ON (daily_report_id) id
                  FROM daily_report_photos
                  WHERE deleted_at IS NULL
                  ORDER BY daily_report_id, created_at DESC, id DESC
              )
        SQL);

        Schema::table('daily_report_photos', function (Blueprint $table) {
            $table->renameColumn('file_path', 'before_file_path');
            $table->renameColumn('thumbnail_path', 'before_thumbnail_path');
            $table->renameColumn('caption', 'description');
            $table->string('after_file_path')->nullable();
            $table->string('after_thumbnail_path')->nullable();
            $table->timestamp('captured_at')->nullable();
        });

        // One before/after pair per report (= per shift) while active.
        DB::statement(<<<'SQL'
            CREATE UNIQUE INDEX daily_report_photos_report_active_unique
            ON daily_report_photos (daily_report_id)
            WHERE deleted_at IS NULL
        SQL);
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS daily_report_photos_report_active_unique');

        Schema::table('daily_report_photos', function (Blueprint $table) {
            $table->renameColumn('before_file_path', 'file_path');
            $table->renameColumn('before_thumbnail_path', 'thumbnail_path');
            $table->renameColumn('description', 'caption');
            $table->dropColumn(['after_file_path', 'after_thumbnail_path', 'captured_at']);
        });
    }
};
