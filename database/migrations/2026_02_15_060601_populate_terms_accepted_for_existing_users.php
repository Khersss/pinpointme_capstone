<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Auto-accept terms for all existing users so only NEW registrations need to accept.
     */
    public function up(): void
    {
        // Set terms_accepted_at for all existing users who don't have it set
        // This ensures only newly registered accounts will see the terms acceptance page
        DB::table('users')
            ->whereNull('terms_accepted_at')
            ->update([
                'terms_accepted_at' => now(),
                'terms_accepted_version' => '1.0',
                'terms_accepted_ip' => '127.0.0.1', // Legacy marker for existing users
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Only clear terms for users marked with legacy IP (existing users at migration time)
        DB::table('users')
            ->where('terms_accepted_ip', '127.0.0.1')
            ->update([
                'terms_accepted_at' => null,
                'terms_accepted_version' => null,
                'terms_accepted_ip' => null,
            ]);
    }
};
