<?php

namespace Illuminate\Tests\Integration\Foundation\Fixtures\Logs;

use Exception;
use Monolog\Handler\AbstractProcessingHandler;

class ThrowExceptionLogHandler extends AbstractProcessingHandler
{
    protected function write(array $record): void
    {
        throw new Exception('Thrown inside ThrowExceptionLogHandler');
    }
}
