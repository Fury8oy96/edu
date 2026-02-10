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
        Schema::create('assessment_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attempt_id')->constrained('assessment_attempts')->onDelete('cascade');
            $table->foreignId('question_id')->constrained('assessment_questions')->onDelete('cascade');
            $table->json('answer');
            $table->boolean('is_correct')->nullable()->comment('for auto-graded questions');
            $table->decimal('points_earned', 5, 2)->nullable();
            $table->enum('grading_status', ['auto_graded', 'pending_review', 'manually_graded']);
            $table->text('grader_feedback')->nullable();
            $table->foreignId('graded_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('graded_at')->nullable();
            $table->timestamps();

            // Unique constraint for one answer per question per attempt
            $table->unique(['attempt_id', 'question_id'], 'unique_answer');
            
            // Indexes
            $table->index('grading_status', 'idx_grading_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assessment_answers');
    }
};
