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
        Schema::create('video_qualities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('video_id')->constrained('videos')->onDelete('cascade');
            $table->string('quality', 10)->comment('360p, 480p, 720p, 1080p');
            $table->string('file_path', 500);
            $table->unsignedBigInteger('file_size');
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->unsignedTinyInteger('processing_progress')->default(0);
            $table->text('error_message')->nullable();
            $table->timestamps();

            // Unique constraint on (video_id, quality)
            $table->unique(['video_id', 'quality'], 'unique_video_quality');
            
            // Index on (video_id, status)
            $table->index(['video_id', 'status'], 'idx_video_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('video_qualities');
    }
};
