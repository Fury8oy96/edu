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
        Schema::create('videos', function (Blueprint $table) {
            $table->id();
            $table->string('original_filename');
            $table->string('display_name');
            $table->unsignedBigInteger('file_size');
            $table->decimal('duration', 10, 2)->nullable()->comment('Duration in seconds');
            $table->string('resolution', 50)->nullable()->comment('e.g., 1920x1080');
            $table->string('codec', 50)->nullable();
            $table->string('format', 50)->nullable();
            $table->string('original_path', 500);
            $table->string('thumbnail_path', 500)->nullable();
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->unsignedTinyInteger('processing_progress')->default(0)->comment('0-100');
            $table->text('error_message')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            // Indexes
            $table->index('status');
            $table->index('uploaded_by');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('videos');
    }
};
