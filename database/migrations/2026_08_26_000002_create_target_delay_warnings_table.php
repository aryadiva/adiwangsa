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
        Schema::create('target_delay_warnings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('milestone_sub_job_id')->constrained('milestone_sub_jobs')->restrictOnDelete();
            $table->foreignUuid('project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->foreignUuid('site_id')->nullable()->constrained('sites')->nullOnDelete();
            $table->foreignUuid('daily_report_id')->nullable()->constrained('daily_reports')->nullOnDelete();
            $table->date('report_date');
            $table->decimal('baseline_target', 12, 2)->nullable();
            $table->decimal('carried_deficit', 12, 2)->nullable();
            $table->decimal('daily_target', 12, 2)->nullable();
            $table->decimal('actual_progress', 12, 2)->nullable();
            $table->decimal('deficit', 12, 2)->nullable();
            $table->timestamp('first_triggered_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->jsonb('meta_data')->default('{}');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['milestone_sub_job_id', 'resolved_at']);
            $table->index(['project_id', 'report_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('target_delay_warnings');
    }
};
