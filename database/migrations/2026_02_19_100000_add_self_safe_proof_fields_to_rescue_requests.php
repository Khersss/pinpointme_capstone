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
            // Self-safe proof fields
            $table->timestamp('marking_safe_in_progress_at')->nullable()->after('cancel_in_progress_at');
            $table->string('safe_proof_photo')->nullable()->after('marking_safe_in_progress_at');
            $table->text('safe_proof_reason')->nullable()->after('safe_proof_photo');
            $table->timestamp('self_marked_safe_at')->nullable()->after('safe_proof_reason');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rescue_requests', function (Blueprint $table) {
            $table->dropColumn([
                'marking_safe_in_progress_at',
                'safe_proof_photo',
                'safe_proof_reason',
                'self_marked_safe_at',
            ]);
        });
    }
};
