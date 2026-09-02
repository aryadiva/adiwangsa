<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sub_job_delay_events', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('milestone_sub_job_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('red');
            $table->timestamp('triggered_at');
            $table->text('mitigation_plan')->nullable();
            $table->foreignUuid('mitigation_submitted_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->integer('delay_days')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['milestone_sub_job_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sub_job_delay_events');
    }
};
