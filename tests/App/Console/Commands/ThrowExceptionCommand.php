<?php

namespace Illuminate\Tests\App\Console\Commands;

use Exception;
use Illuminate\Console\Command;

class ThrowExceptionCommand extends Command
{
    protected $signature = 'throw-exception-command';

    public function handle()
    {
        throw new Exception('Thrown inside ThrowExceptionCommand');
    }
}
