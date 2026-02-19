<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rescue_requests', function (Blueprint $table) {
            $table->string('completion_photo')->nullable()->after('additional_info');
            $table->text('completion_notes')->nullable()->after('completion_photo');
        });
    }

    public function down(): void
    {
        Schema::table('rescue_requests', function (Blueprint $table) {
            $table->dropColumn(['completion_photo', 'completion_notes']);
        });
    }
};
