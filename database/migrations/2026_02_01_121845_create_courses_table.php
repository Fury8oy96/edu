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
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description');
            $table->string('image');
            $table->decimal('price', 10, 2);
            $table->string('status');
            $table->string('language');
            $table->string('level');
            $table->string('category');
            $table->string('subcategory');
            $table->string('tags');
            $table->string('keywords');
            $table->string('requirements');
            $table->string('outcomes');
            $table->string('target_audience');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};
