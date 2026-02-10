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
        Schema::create('assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained('courses')->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedInteger('time_limit')->comment('minutes');
            $table->decimal('passing_score', 5, 2)->comment('percentage 0-100');
            $table->unsignedInteger('max_attempts')->nullable()->comment('null = unlimited');
            $table->timestamp('start_date')->nullable();
            $table->timestamp('end_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Indexes
            $table->index(['course_id', 'is_active'], 'idx_course_active');
            $table->index(['start_date', 'end_date'], 'idx_availability');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assessments');
    }
};
