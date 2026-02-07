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
            $table->boolean('is_paid')->default(false)->after('price');
            $table->timestamp('published_at')->nullable()->after('status');
            $table->integer('duration_hours')->nullable()->after('published_at');
            $table->integer('enrollment_count')->default(0)->after('duration_hours');
            
            $table->index('is_paid');
            $table->index('status');
            $table->index('published_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropIndex(['is_paid']);
            $table->dropIndex(['status']);
            $table->dropIndex(['published_at']);
            $table->dropColumn([
                'is_paid',
                'published_at',
                'duration_hours',
                'enrollment_count'
            ]);
        });
    }
};
