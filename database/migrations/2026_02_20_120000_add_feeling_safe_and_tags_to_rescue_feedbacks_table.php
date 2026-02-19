<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rescue_feedbacks', function (Blueprint $table) {
            $table->boolean('feeling_safe_now')->nullable()->after('liked_most');
            $table->json('feedback_tags')->nullable()->after('feeling_safe_now');
        });
    }

    public function down(): void
    {
        Schema::table('rescue_feedbacks', function (Blueprint $table) {
            $table->dropColumn(['feeling_safe_now', 'feedback_tags']);
        });
    }
};
