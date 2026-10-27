<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('failed_jobs', 'payload')) {
            Schema::table('failed_jobs', function (Blueprint $table): void {
                $table->longText('payload')->nullable()->after('queue');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('failed_jobs', 'payload')) {
            Schema::table('failed_jobs', function (Blueprint $table): void {
                $table->dropColumn('payload');
            });
        }
    }
};
