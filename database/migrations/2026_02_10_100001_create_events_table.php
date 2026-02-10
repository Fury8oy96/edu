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
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description');
            $table->timestamp('start_time')->nullable();
            $table->timestamp('end_time')->nullable();
            $table->enum('state', ['upcoming', 'ongoing', 'past'])->default('upcoming');
            $table->integer('max_participants')->nullable(); // null = unlimited
            $table->integer('registration_count')->default(0);
            $table->integer('participation_count')->default(0);
            $table->integer('attendance_count')->default(0);
            $table->timestamps();
            
            // Indexes for performance
            $table->index('state');
            $table->index('start_time');
            $table->index('end_time');
            $table->index(['state', 'start_time']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
