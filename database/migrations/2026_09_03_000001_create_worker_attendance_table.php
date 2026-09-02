<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('worker_attendance', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('worker_id')->constrained('workers')->restrictOnDelete();
            $table->foreignUuid('site_id')->constrained('sites')->restrictOnDelete();
            $table->foreignUuid('recorded_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('attendance_date');
            $table->decimal('hours_worked', 4, 2)->default(8.00);
            $table->decimal('overtime_hours', 4, 2)->default(0.00);
            $table->string('photo_file_path')->nullable();
            $table->string('photo_thumbnail_path')->nullable();
            $table->timestamp('captured_at')->nullable();
            $table->jsonb('meta_data')->default('{}');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['site_id', 'attendance_date']);
        });

        // One active attendance record per worker per day (app-layer friendly
        // error is the first line of defense; this partial index ignores
        // soft-deleted rows as a backstop — same pattern as daily_reports).
        DB::statement(
            'CREATE UNIQUE INDEX worker_attendance_worker_date_active_unique '
            .'ON worker_attendance (worker_id, attendance_date) WHERE deleted_at IS NULL'
        );
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS worker_attendance_worker_date_active_unique');

        Schema::dropIfExists('worker_attendance');
    }
};
