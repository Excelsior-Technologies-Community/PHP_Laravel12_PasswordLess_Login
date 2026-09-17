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
        Schema::table('users', function (Blueprint $table) {
            $table->string('login_otp', 10)->nullable()->after('login_token');
            $table->timestamp('otp_expires_at')->nullable()->after('token_expires_at');
            $table->unsignedTinyInteger('otp_attempts')->default(0)->after('otp_expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['login_otp', 'otp_expires_at', 'otp_attempts']);
        });
    }
};
