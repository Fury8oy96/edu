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
        Schema::create('course_student', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained('courses')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->timestamp('enrolled_at')->useCurrent();
            $table->enum('status', ['active', 'completed', 'dropped'])->default('active');
            $table->integer('progress_percentage')->default(0);
            $table->timestamps();

            $table->unique(['course_id', 'student_id']);
            $table->index('course_id');
            $table->index('student_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_student');
    }
};
