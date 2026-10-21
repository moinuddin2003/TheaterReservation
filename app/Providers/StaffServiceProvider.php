<?php

namespace App\Providers;

use App\Contracts\Staff\StaffData;
use App\Contracts\Staff\TicketScanner;
use App\Services\Staff\EloquentStaffData;
use App\Services\Staff\EloquentTicketScanner;
use App\Services\Staff\MockStaffData;
use App\Services\Staff\MockTicketScanner;
use App\Services\Staff\ScanRules;
use Illuminate\Support\ServiceProvider;

/**
 * Chooses where the staff screens get their data: "database" by default,
 * "mock" only when config('staff.data_source') is changed (see config/staff.php).
 */
class StaffServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ScanRules::class, fn () => ScanRules::fromConfig());

        $this->app->bind(StaffData::class, fn ($app) => $this->useDatabase()
            ? $app->make(EloquentStaffData::class)
            : $app->make(MockStaffData::class));

        $this->app->bind(TicketScanner::class, fn ($app) => $this->useDatabase()
            ? $app->make(EloquentTicketScanner::class)
            : MockTicketScanner::fromConfig());
    }

    private function useDatabase(): bool
    {
        return config('staff.data_source') === 'database';
    }
}
