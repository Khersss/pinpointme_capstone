<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rescue_feedbacks', function (Blueprint $table) {
            $table->string('liked_most')->nullable()->after('would_recommend');
        });
    }

    public function down(): void
    {
        Schema::table('rescue_feedbacks', function (Blueprint $table) {
            $table->dropColumn('liked_most');
        });
    }
};
