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
        Schema::create('assessment_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_id')->constrained('assessments')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->unsignedInteger('attempt_number');
            $table->enum('status', ['in_progress', 'completed', 'timed_out', 'grading_pending']);
            $table->timestamp('start_time');
            $table->timestamp('completion_time')->nullable();
            $table->unsignedInteger('time_taken')->nullable()->comment('seconds');
            $table->decimal('score', 6, 2)->nullable();
            $table->decimal('max_score', 6, 2);
            $table->decimal('percentage', 5, 2)->nullable();
            $table->boolean('passed')->nullable();
            $table->timestamps();

            // Unique constraint for attempt number per student per assessment
            $table->unique(['assessment_id', 'student_id', 'attempt_number'], 'unique_attempt');
            
            // Indexes
            $table->index(['student_id', 'assessment_id'], 'idx_student_assessment');
            $table->index('status', 'idx_assessment_attempts_status');
            $table->index('completion_time', 'idx_completion');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assessment_attempts');
    }
};
