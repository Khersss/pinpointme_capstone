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
            $table->boolean('cancel_approval_requested')->default(false)->after('safe_approval_reason');
            $table->timestamp('cancel_approval_requested_at')->nullable()->after('cancel_approval_requested');
            $table->enum('cancel_approval_status', ['pending', 'approved', 'denied'])->nullable()->after('cancel_approval_requested_at');
            $table->timestamp('cancel_approval_responded_at')->nullable()->after('cancel_approval_status');
            $table->string('cancel_approval_reason', 500)->nullable()->after('cancel_approval_responded_at');
            $table->string('cancel_proof_details', 1000)->nullable()->after('cancel_approval_reason');
            $table->integer('cancel_attempt_count')->default(0)->after('cancel_proof_details');
            $table->timestamp('last_cancel_attempt_at')->nullable()->after('cancel_attempt_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rescue_requests', function (Blueprint $table) {
            $table->dropColumn([
                'cancel_approval_requested',
                'cancel_approval_requested_at',
                'cancel_approval_status',
                'cancel_approval_responded_at',
                'cancel_approval_reason',
                'cancel_proof_details',
                'cancel_attempt_count',
                'last_cancel_attempt_at',
            ]);
        });
    }
};
