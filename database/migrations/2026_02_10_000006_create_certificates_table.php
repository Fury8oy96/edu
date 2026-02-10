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
        Schema::create('certificates', function (Blueprint $table) {
            $table->id();
            
            // Unique certificate identifier
            $table->string('certificate_id', 20)->unique();
            
            // Foreign keys
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->foreignId('course_id')->constrained('courses')->onDelete('cascade');
            
            // Denormalized student data for data integrity
            $table->string('student_name');
            $table->string('student_email');
            
            // Denormalized course data for data integrity
            $table->string('course_title');
            $table->string('instructor_name');
            $table->string('course_duration', 50)->nullable();
            
            // Certificate data
            $table->timestamp('completion_date');
            $table->string('grade', 20);
            $table->decimal('average_score', 5, 2)->nullable();
            $table->json('assessment_scores')->nullable();
            $table->string('verification_url', 500);
            $table->string('pdf_path', 500)->nullable();
            
            // Issuance tracking
            $table->enum('issued_by', ['system', 'admin'])->default('system');
            $table->foreignId('issued_by_admin_id')->nullable()->constrained('users')->onDelete('set null');
            
            // Status and revocation
            $table->enum('status', ['active', 'revoked'])->default('active');
            $table->timestamp('revoked_at')->nullable();
            $table->foreignId('revoked_by_admin_id')->nullable()->constrained('users')->onDelete('set null');
            $table->text('revocation_reason')->nullable();
            
            $table->timestamps();
            
            // Indexes for performance
            $table->index('student_id');
            $table->index('course_id');
            $table->index('certificate_id');
            $table->index('status');
            $table->index('completion_date');
            $table->index('grade');
            
            // Unique constraint to prevent duplicate certificates
            $table->unique(['student_id', 'course_id'], 'unique_student_course');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('certificates');
    }
};
