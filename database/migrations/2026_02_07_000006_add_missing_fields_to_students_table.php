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
        Schema::table('students', function (Blueprint $table) {
            $table->string('profession')->nullable()->after('email');
            $table->timestamp('email_verified_at')->nullable()->after('profession');
            $table->string('otp_code', 6)->nullable()->after('password');
            $table->timestamp('otp_expires_at')->nullable()->after('otp_code');
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active')->after('otp_expires_at');
            $table->rememberToken()->after('status');
            
            $table->index('email');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropIndex(['email']);
            $table->dropIndex(['status']);
            $table->dropColumn([
                'profession',
                'email_verified_at',
                'otp_code',
                'otp_expires_at',
                'status',
                'remember_token'
            ]);
        });
    }
};
