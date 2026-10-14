<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticket_scans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('showtime_id')->nullable()->constrained()->nullOnDelete();
            $table->char('token_hash', 64);
            $table->foreignId('scanned_by')->constrained('users')->restrictOnDelete();
            $table->string('result', 30);
            $table->string('message', 255)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->dateTime('scanned_at');

            $table->index(['showtime_id', 'scanned_at']);
            $table->index('scanned_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_scans');
    }
};
