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
        Schema::create('projects', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('client_id')->constrained('clients')->restrictOnDelete();
            $table->string('name');
            $table->string('code')->unique();
            $table->enum('status', ['planning', 'active', 'on_hold', 'completed'])->default('planning');
            $table->date('start_date');
            $table->date('target_end_date')->nullable();
            $table->decimal('budget', 15, 2)->nullable();
            $table->string('timezone')->default('UTC');
            $table->jsonb('meta_data')->default('{}');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['client_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
