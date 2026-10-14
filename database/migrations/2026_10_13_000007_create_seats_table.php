<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('screen_id')->constrained()->cascadeOnDelete();
            $table->string('row_label', 3);
            $table->unsignedTinyInteger('seat_number');
            $table->string('label', 6);
            $table->string('type', 20)->default('standard');
            $table->boolean('is_disabled')->default(false);
            $table->timestamps();

            $table->unique(['screen_id', 'row_label', 'seat_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seats');
    }
};
