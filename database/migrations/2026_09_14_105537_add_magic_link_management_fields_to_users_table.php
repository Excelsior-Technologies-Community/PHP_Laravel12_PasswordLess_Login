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
            $table->timestamp('magic_link_requested_at')
                ->nullable()
                ->after('token_expires_at');

            $table->timestamp('magic_link_revoked_at')
                ->nullable()
                ->after('magic_link_requested_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'magic_link_requested_at',
                'magic_link_revoked_at',
            ]);
        });
    }
};