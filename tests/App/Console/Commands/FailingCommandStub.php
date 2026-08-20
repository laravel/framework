<?php

namespace Illuminate\Tests\App\Console\Commands;

use Illuminate\Console\Command;

class FailingCommandStub extends Command
{
    protected $signature = 'app:fail';

    public function handle()
    {
        $this->trigger_failure();

        // This should never be reached.
        return static::SUCCESS;
    }

    protected function trigger_failure()
    {
        $this->fail('Whoops!');
    }
}
