<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movies', function (Blueprint $table) {
            $table->id();
            $table->string('title', 200)->index();
            $table->string('slug', 220)->unique();
            $table->text('synopsis');
            $table->unsignedSmallInteger('duration_minutes');
            $table->string('rating', 10)->nullable();
            $table->date('release_date')->nullable();
            $table->string('poster_path', 255)->nullable();
            $table->string('trailer_url', 255)->nullable();
            $table->string('status', 20)->default('coming_soon');
            $table->timestamps();

            $table->index(['status', 'release_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movies');
    }
};
