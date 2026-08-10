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
        Schema::create('daily_report_workers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('daily_report_id')->constrained('daily_reports')->cascadeOnDelete();
            $table->foreignUuid('worker_id')->constrained('workers')->restrictOnDelete();
            $table->decimal('hours_worked', 4, 2)->default(8.00);
            $table->string('remarks')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_report_workers');
    }
};
