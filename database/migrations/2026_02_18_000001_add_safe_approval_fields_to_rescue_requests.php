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
        Schema::table('rescue_requests', function (Blueprint $table) {
            $table->boolean('safe_approval_requested')->default(false)->after('cancelled_at');
            $table->timestamp('safe_approval_requested_at')->nullable()->after('safe_approval_requested');
            $table->enum('safe_approval_status', ['pending', 'approved', 'denied'])->nullable()->after('safe_approval_requested_at');
            $table->timestamp('safe_approval_responded_at')->nullable()->after('safe_approval_status');
            $table->string('safe_approval_reason', 500)->nullable()->after('safe_approval_responded_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rescue_requests', function (Blueprint $table) {
            $table->dropColumn([
                'safe_approval_requested',
                'safe_approval_requested_at',
                'safe_approval_status',
                'safe_approval_responded_at',
                'safe_approval_reason',
            ]);
        });
    }
};
