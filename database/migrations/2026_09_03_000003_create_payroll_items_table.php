<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_items', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('payroll_run_id')->constrained('payroll_runs')->cascadeOnDelete();
            $table->foreignUuid('worker_id')->constrained('workers')->restrictOnDelete();
            $table->decimal('regular_hours_total', 6, 2)->default(0.00);
            $table->decimal('overtime_hours_total', 6, 2)->default(0.00);
            $table->decimal('regular_pay', 12, 2)->default(0.00);
            $table->decimal('overtime_pay', 12, 2)->default(0.00);
            $table->decimal('total_pay', 12, 2)->default(0.00);
            $table->timestamps();

            $table->index(['payroll_run_id', 'worker_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_items');
    }
};
