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
        Schema::table('daily_reports', function (Blueprint $table) {
            $table->foreignUuid('milestone_sub_job_id')
                ->nullable()
                ->after('site_id')
                ->constrained('milestone_sub_jobs')
                ->restrictOnDelete();
            $table->enum('shift', ['shift_1', 'shift_2', 'shift_3'])
                ->default('shift_1')
                ->after('report_date');
            $table->decimal('daily_achievement', 12, 2)
                ->nullable()
                ->after('delays_or_issues');
            $table->decimal('daily_target', 12, 2)
                ->nullable()
                ->after('daily_achievement');
            $table->text('delay_reason')
                ->nullable()
                ->after('daily_target');

            $table->index('milestone_sub_job_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('daily_reports', function (Blueprint $table) {
            $table->dropIndex(['milestone_sub_job_id']);
            $table->dropConstrainedForeignId('milestone_sub_job_id');
            $table->dropColumn(['shift', 'daily_achievement', 'daily_target', 'delay_reason']);
        });
    }
};
