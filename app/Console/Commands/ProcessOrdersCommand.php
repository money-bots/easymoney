<?php

namespace App\Console\Commands;

use App\Jobs\ProcessOrders;
use Illuminate\Console\Command;

class ProcessOrdersCommand extends Command
{
    protected $signature = 'run:orders';

    protected $description = 'Create process orders';

    public function handle(): void
    {
        ProcessOrders::dispatch();
    }
}
