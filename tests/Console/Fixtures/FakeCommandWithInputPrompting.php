<?php

namespace Illuminate\Tests\Console\Fixtures;

use Illuminate\Console\Command;
use Illuminate\Contracts\Console\PromptsForMissingInput;

class FakeCommandWithInputPrompting extends Command implements PromptsForMissingInput
{
    use \Illuminate\Console\Concerns\PromptsForMissingInput;

    protected $signature = 'fake-command-for-testing {name : An argument}';

    public function handle(): int
    {
        return self::SUCCESS;
    }
}
