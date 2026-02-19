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
        Schema::create('rescue_feedbacks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rescue_request_id')->constrained('rescue_requests')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('rescuer_id')->nullable()->constrained('users')->onDelete('set null');
            $table->unsignedTinyInteger('overall_rating'); // 1-5 stars
            $table->unsignedTinyInteger('response_time_rating')->nullable(); // 1-5
            $table->unsignedTinyInteger('communication_rating')->nullable(); // 1-5
            $table->unsignedTinyInteger('professionalism_rating')->nullable(); // 1-5
            $table->text('comments')->nullable();
            $table->boolean('would_recommend')->default(true);
            $table->timestamps();

            // Each rescue can only have one feedback from the user
            $table->unique(['rescue_request_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rescue_feedbacks');
    }
};
