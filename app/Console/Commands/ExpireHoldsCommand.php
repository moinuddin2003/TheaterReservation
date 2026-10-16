<?php

namespace App\Console\Commands;

use App\Actions\ExpireHoldsAction;
use Illuminate\Console\Command;

class ExpireHoldsCommand extends Command
{
    protected $signature = 'cinebook:expire-holds';

    protected $description = 'Expire overdue seat holds and release their inventory.';

    public function handle(ExpireHoldsAction $action): int
    {
        $count = $action->execute();
        $this->info("Expired {$count} seat holds.");

        return self::SUCCESS;
    }
}
