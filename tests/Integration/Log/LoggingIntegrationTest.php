<?php

namespace Illuminate\Tests\Integration\Log;

use Illuminate\Support\Facades\Log;
use Orchestra\Testbench\TestCase;

class LoggingIntegrationTest extends TestCase
{
    public function testLoggingCanBeRunWithoutEncounteringExceptions()
    {
        $this->expectNotToPerformAssertions();

        Log::info('Hello World');
    }
}
