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
        Schema::create('blog_posts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id');
            $table->string('title', 200);
            $table->string('slug', 220)->unique();
            $table->text('content');
            $table->string('excerpt', 300)->nullable();
            $table->string('featured_image', 255)->nullable();
            $table->enum('status', ['draft', 'published'])->default('draft');
            $table->unsignedBigInteger('category_id')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('student_id')->references('id')->on('students')->onDelete('cascade');
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('set null');
            
            // Indexes
            $table->index('status', 'idx_status');
            $table->index('published_at', 'idx_published_at');
            $table->index('student_id', 'idx_student_id');
            
            // Full-text index for search (only for MySQL/PostgreSQL)
            $driver = Schema::getConnection()->getDriverName();
            if ($driver !== 'sqlite') {
                $table->fullText(['title', 'content'], 'idx_search');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('blog_posts');
    }
};
