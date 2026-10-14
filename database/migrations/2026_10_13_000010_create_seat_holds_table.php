<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seat_holds', function (Blueprint $table) {
            $table->id();
            $table->uuid('hold_token')->index();
            $table->foreignId('showtime_id')->constrained()->restrictOnDelete();
            $table->foreignId('seat_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('booking_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('price_cents');
            $table->string('status', 20)->default('active');
            // 1 only while active, NULL otherwise: lets a unique index act as a partial unique index
            $table->tinyInteger('active_key')->nullable()->storedAs("IF(status = 'active', 1, NULL)");
            $table->dateTime('expires_at');
            $table->dateTime('released_at')->nullable();
            $table->timestamps();

            $table->unique(['showtime_id', 'seat_id', 'active_key'], 'seat_holds_active_unique');
            $table->index(['status', 'expires_at']);
            $table->index(['showtime_id', 'status']);
            $table->index(['user_id', 'showtime_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seat_holds');
    }
};
