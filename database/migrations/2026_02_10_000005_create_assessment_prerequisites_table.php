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
        Schema::create('assessment_prerequisites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_id')->constrained('assessments')->onDelete('cascade');
            $table->enum('prerequisite_type', ['quiz_completion', 'minimum_progress', 'lesson_completion']);
            $table->json('prerequisite_data')->comment('type-specific configuration');
            $table->timestamps();

            // Index
            $table->index('assessment_id', 'idx_assessment');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assessment_prerequisites');
    }
};
