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
        Schema::create('assessment_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_id')->constrained('assessments')->onDelete('cascade');
            $table->enum('question_type', ['multiple_choice', 'true_false', 'short_answer', 'essay']);
            $table->text('question_text');
            $table->decimal('points', 5, 2);
            $table->unsignedInteger('order');
            $table->json('options')->nullable()->comment('for multiple_choice');
            $table->json('correct_answer')->nullable();
            $table->text('grading_rubric')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['assessment_id', 'order'], 'idx_assessment_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assessment_questions');
    }
};
