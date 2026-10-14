<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('showtime_id')->constrained()->restrictOnDelete();
            $table->foreignId('seat_id')->constrained()->restrictOnDelete();
            $table->string('seat_label', 6);
            $table->string('seat_type', 20);
            $table->unsignedInteger('price_cents');
            $table->string('status', 20)->default('active');
            $table->tinyInteger('active_key')->nullable()->storedAs("IF(status = 'active', 1, NULL)");
            $table->timestamps();

            $table->unique(['showtime_id', 'seat_id', 'active_key'], 'booking_items_active_unique');
            $table->unique(['booking_id', 'seat_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_items');
    }
};
