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
        Schema::table('courses', function (Blueprint $table) {
            $table->foreignId('instructor_id')
                  ->nullable()
                  ->after('id')
                  ->constrained('instructors')
                  ->nullOnDelete();
            
            $table->index('instructor_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropForeign(['instructor_id']);
            $table->dropIndex(['instructor_id']);
            $table->dropColumn('instructor_id');
        });
    }
};
