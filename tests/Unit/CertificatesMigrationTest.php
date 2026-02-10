<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

test('certificates table has all required columns', function () {
    expect(Schema::hasTable('certificates'))->toBeTrue();
    
    $columns = Schema::getColumnListing('certificates');
    
    // Primary key
    expect($columns)->toContain('id');
    
    // Unique identifier
    expect($columns)->toContain('certificate_id');
    
    // Foreign keys
    expect($columns)->toContain('student_id');
    expect($columns)->toContain('course_id');
    
    // Denormalized student data
    expect($columns)->toContain('student_name');
    expect($columns)->toContain('student_email');
    
    // Denormalized course data
    expect($columns)->toContain('course_title');
    expect($columns)->toContain('instructor_name');
    expect($columns)->toContain('course_duration');
    
    // Certificate data
    expect($columns)->toContain('completion_date');
    expect($columns)->toContain('grade');
    expect($columns)->toContain('average_score');
    expect($columns)->toContain('assessment_scores');
    expect($columns)->toContain('verification_url');
    expect($columns)->toContain('pdf_path');
    
    // Issuance tracking
    expect($columns)->toContain('issued_by');
    expect($columns)->toContain('issued_by_admin_id');
    
    // Status and revocation
    expect($columns)->toContain('status');
    expect($columns)->toContain('revoked_at');
    expect($columns)->toContain('revoked_by_admin_id');
    expect($columns)->toContain('revocation_reason');
    
    // Timestamps
    expect($columns)->toContain('created_at');
    expect($columns)->toContain('updated_at');
});

test('certificates table has required indexes', function () {
    $indexes = Schema::getIndexes('certificates');
    $indexColumns = array_column($indexes, 'columns');
    
    // Check for individual column indexes
    expect($indexColumns)->toContain(['student_id']);
    expect($indexColumns)->toContain(['course_id']);
    expect($indexColumns)->toContain(['certificate_id']);
    expect($indexColumns)->toContain(['status']);
    expect($indexColumns)->toContain(['completion_date']);
    expect($indexColumns)->toContain(['grade']);
});

test('certificates table has unique constraint on student_id and course_id', function () {
    $indexes = Schema::getIndexes('certificates');
    
    $uniqueConstraint = collect($indexes)->first(function ($index) {
        return $index['name'] === 'unique_student_course' && $index['unique'] === true;
    });
    
    expect($uniqueConstraint)->not->toBeNull();
    expect($uniqueConstraint['columns'])->toBe(['student_id', 'course_id']);
});

test('certificates table has foreign key constraints', function () {
    $foreignKeys = Schema::getForeignKeys('certificates');
    $foreignKeyColumns = array_column($foreignKeys, 'columns');
    
    // Check that foreign keys exist
    expect($foreignKeyColumns)->toContain(['student_id']);
    expect($foreignKeyColumns)->toContain(['course_id']);
    expect($foreignKeyColumns)->toContain(['issued_by_admin_id']);
    expect($foreignKeyColumns)->toContain(['revoked_by_admin_id']);
});

test('certificates table foreign keys have correct cascade behavior', function () {
    $foreignKeys = Schema::getForeignKeys('certificates');
    
    // Find student_id foreign key
    $studentFk = collect($foreignKeys)->first(fn($fk) => $fk['columns'] === ['student_id']);
    expect($studentFk)->not->toBeNull();
    expect($studentFk['foreign_table'])->toBe('students');
    expect($studentFk['on_delete'])->toBe('cascade');
    
    // Find course_id foreign key
    $courseFk = collect($foreignKeys)->first(fn($fk) => $fk['columns'] === ['course_id']);
    expect($courseFk)->not->toBeNull();
    expect($courseFk['foreign_table'])->toBe('courses');
    expect($courseFk['on_delete'])->toBe('cascade');
    
    // Find issued_by_admin_id foreign key
    $issuedByFk = collect($foreignKeys)->first(fn($fk) => $fk['columns'] === ['issued_by_admin_id']);
    expect($issuedByFk)->not->toBeNull();
    expect($issuedByFk['foreign_table'])->toBe('users');
    expect($issuedByFk['on_delete'])->toBe('set null');
    
    // Find revoked_by_admin_id foreign key
    $revokedByFk = collect($foreignKeys)->first(fn($fk) => $fk['columns'] === ['revoked_by_admin_id']);
    expect($revokedByFk)->not->toBeNull();
    expect($revokedByFk['foreign_table'])->toBe('users');
    expect($revokedByFk['on_delete'])->toBe('set null');
});
