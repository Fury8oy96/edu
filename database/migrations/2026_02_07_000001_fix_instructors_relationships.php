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
        // Remove broken foreign keys from instructors table
        Schema::table('instructors', function (Blueprint $table) {
            // Drop indexes first (SQLite requirement)
            $table->dropIndex(['course_id']);
            $table->dropIndex(['lesson_id']);
            
            // Then drop foreign keys
            $table->dropForeign(['course_id']);
            $table->dropForeign(['lesson_id']);
            
            // Finally drop columns
            $table->dropColumn(['course_id', 'lesson_id']);
        });
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
