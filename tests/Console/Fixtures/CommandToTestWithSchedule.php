<?php

declare(strict_types=1);

namespace Illuminate\Tests\Console\Fixtures;

use Illuminate\Console\Command;

final class CommandToTestWithSchedule extends Command
{
    protected $signature = 'test:command';

    protected $description = 'A command for testing';

    public function handle(): void
    {
        //
    }
}
