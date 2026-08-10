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
        Schema::create('daily_reports', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('site_id')->constrained('sites')->restrictOnDelete();
            $table->foreignUuid('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('reviewed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('report_date');
            $table->enum('weather_condition', ['sunny', 'rainy', 'cloudy', 'stormy']);
            $table->text('work_summary');
            $table->text('delays_or_issues')->nullable();
            $table->enum('status', ['draft', 'need_approval', 'published', 'revision_requested'])->default('draft');
            $table->text('admin_notes')->nullable();
            $table->jsonb('meta_data')->default('{}');
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('report_date');
            $table->unique(['site_id', 'report_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_reports');
    }
};
