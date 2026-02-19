<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_notifications', function (Blueprint $table) {
            $table->id();
            $table->string('type', 100);             // e.g. rescue_cancelled, safe_request, etc.
            $table->string('title', 255);
            $table->text('message');
            $table->json('data')->nullable();         // Extra payload (rescue_id, reason, etc.)
            $table->timestamp('read_at')->nullable(); // null = unread
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_notifications');
    }
};
