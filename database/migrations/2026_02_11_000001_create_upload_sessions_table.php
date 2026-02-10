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
        Schema::create('upload_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('session_id')->unique();
            $table->string('filename');
            $table->unsignedBigInteger('file_size');
            $table->unsignedInteger('total_chunks');
            $table->json('received_chunks')->default('[]');
            $table->enum('status', ['pending', 'completed', 'failed', 'expired'])->default('pending');
            $table->timestamps();

            // Indexes
            $table->index('session_id');
            $table->index(['status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('upload_sessions');
    }
};
