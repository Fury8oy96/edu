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
        // Fix courses table
        Schema::table('courses', function (Blueprint $table) {
            $table->json('tags')->nullable()->change();
            $table->json('keywords')->nullable()->change();
            $table->text('requirements')->nullable()->change();
            $table->text('outcomes')->nullable()->change();
            $table->string('image')->nullable()->change();
        });

        // Fix modules table
        Schema::table('modules', function (Blueprint $table) {
            $table->json('tags')->nullable()->change();
            $table->json('keywords')->nullable()->change();
            $table->text('requirements')->nullable()->change();
            $table->text('outcomes')->nullable()->change();
        });

        // Fix lessons table
        Schema::table('lessons', function (Blueprint $table) {
            $table->json('tags')->nullable()->change();
            $table->json('keywords')->nullable()->change();
            $table->text('requirements')->nullable()->change();
            $table->text('outcomes')->nullable()->change();
            $table->string('video_url')->nullable()->change();
        });

        // Fix students table
        Schema::table('students', function (Blueprint $table) {
            $table->string('avatar')->nullable()->change();
            $table->text('bio')->nullable()->change();
            $table->json('skills')->nullable()->change();
            $table->text('experience')->nullable()->change();
            $table->text('education')->nullable()->change();
            $table->json('certifications')->nullable()->change();
        });

        // Fix instructors table
        Schema::table('instructors', function (Blueprint $table) {
            $table->text('bio')->nullable()->change();
            $table->string('avatar')->nullable()->change();
            $table->json('skills')->nullable()->change();
            $table->text('experience')->nullable()->change();
            $table->text('education')->nullable()->change();
            $table->json('certifications')->nullable()->change();
            $table->string('facebook')->nullable()->change();
            $table->string('twitter')->nullable()->change();
            $table->string('instagram')->nullable()->change();
            $table->string('linkedin')->nullable()->change();
            $table->string('youtube')->nullable()->change();
            $table->string('website')->nullable()->change();
            $table->string('github')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverse courses table
        Schema::table('courses', function (Blueprint $table) {
            $table->string('tags')->change();
            $table->string('keywords')->change();
            $table->string('requirements')->change();
            $table->string('outcomes')->change();
            $table->string('image')->change();
        });

        // Reverse modules table
        Schema::table('modules', function (Blueprint $table) {
            $table->string('tags')->change();
            $table->string('keywords')->change();
            $table->string('requirements')->change();
            $table->string('outcomes')->change();
        });

        // Reverse lessons table
        Schema::table('lessons', function (Blueprint $table) {
            $table->string('tags')->change();
            $table->string('keywords')->change();
            $table->string('requirements')->change();
            $table->string('outcomes')->change();
            $table->string('video_url')->change();
        });

        // Reverse students table
        Schema::table('students', function (Blueprint $table) {
            $table->string('avatar')->change();
            $table->string('bio')->change();
            $table->string('skills')->change();
            $table->string('experience')->change();
            $table->string('education')->change();
            $table->string('certifications')->change();
        });

        // Reverse instructors table
        Schema::table('instructors', function (Blueprint $table) {
            $table->string('bio')->change();
            $table->string('avatar')->change();
            $table->text('skills')->change();
            $table->text('experience')->change();
            $table->text('education')->change();
            $table->text('certifications')->change();
            $table->string('facebook')->change();
            $table->string('twitter')->change();
            $table->string('instagram')->change();
            $table->string('linkedin')->change();
            $table->string('youtube')->change();
            $table->string('website')->change();
            $table->string('github')->change();
        });
    }
};
