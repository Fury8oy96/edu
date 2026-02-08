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
        $driver = Schema::getConnection()->getDriverName();
        
        if ($driver === 'sqlite') {
            // SQLite doesn't support dropping columns with foreign keys well
            // So we recreate the table without those columns
            Schema::dropIfExists('instructors');
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
                $table->timestamps();
            });
        } else {
            // For MySQL/PostgreSQL, drop the columns normally
            Schema::table('instructors', function (Blueprint $table) {
                // Drop foreign keys first
                $table->dropForeign(['course_id']);
                $table->dropForeign(['lesson_id']);
                
                // Then drop columns
                $table->dropColumn(['course_id', 'lesson_id']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('instructors', function (Blueprint $table) {
            $table->foreignId('course_id')->constrained('courses')->onDelete('cascade');
            $table->foreignId('lesson_id')->constrained('lessons')->onDelete('cascade');
        });
    }
};
