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
        Schema::create('modules', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description');
            $table->foreignId('course_id')->constrained('courses')->onDelete('cascade');
            $table->string('duration');
            $table->string('status');
            $table->string('keywords');
            $table->string('requirements');
            $table->string('outcomes');
            $table->string('tags');
            $table->timestamps();

            $table->index('course_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('modules');
    }
};
