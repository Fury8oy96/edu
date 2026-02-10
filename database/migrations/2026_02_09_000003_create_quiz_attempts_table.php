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
        Schema::create('quiz_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_id')->constrained()->onDelete('cascade');
            $table->foreignId('student_id')->constrained()->onDelete('cascade');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('deadline')->nullable();
            $table->float('score')->nullable();
            $table->float('score_percentage')->nullable();
            $table->boolean('passed')->default(false);
            $table->boolean('requires_grading')->default(false);
            $table->integer('time_taken_minutes')->nullable();
            $table->timestamps();
            
            $table->index('quiz_id');
            $table->index('student_id');
            $table->index(['quiz_id', 'student_id']);
            $table->index('requires_grading');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quiz_attempts');
    }
};
