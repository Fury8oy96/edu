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
        Schema::create('instructors', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('bio');
            $table->string('avatar');
            $table->text('skills');
            $table->text('experience');
            $table->text('education');
            $table->text('certifications');
            $table->string('facebook');
            $table->string('twitter');
            $table->string('instagram');
            $table->string('linkedin');
            $table->string('youtube');
            $table->string('website');
            $table->string('github');
            $table->foreignId('course_id')->constrained('courses')->onDelete('cascade');
            $table->foreignId('lesson_id')->constrained('lessons')->onDelete('cascade');
            $table->timestamps();

            $table->index('course_id');
            $table->index('lesson_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('instructors');
    }
};
