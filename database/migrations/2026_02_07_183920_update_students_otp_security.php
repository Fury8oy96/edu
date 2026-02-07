<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Security improvement: Store hashed OTP instead of plain text
     * OTP will be generated, hashed, and stored temporarily for verification
     */
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            // Rename and modify otp_code to otp_hash with longer length
            $table->dropColumn('otp_code');
        });
        
        Schema::table('students', function (Blueprint $table) {
            $table->string('otp_hash', 255)->nullable()->after('password');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn('otp_hash');
        });
        
        Schema::table('students', function (Blueprint $table) {
            $table->string('otp_code', 6)->nullable()->after('password');
        });
    }
};
