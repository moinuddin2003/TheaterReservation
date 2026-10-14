<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('screens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cinema_id')->constrained()->restrictOnDelete();
            $table->string('name', 60);
            $table->unsignedTinyInteger('rows_count');
            $table->unsignedTinyInteger('seats_per_row');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['cinema_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('screens');
    }
};
