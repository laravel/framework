<?php

namespace Illuminate\Tests\Log;

use Illuminate\Log\LogManager;
use Orchestra\Testbench\TestCase;

class LogManagerTest extends TestCase
{
    public function testLogManagerCachesLoggerInstances()
    {
        $manager = new LogManager($this->app);

        $logger1 = $manager->channel('single')->getLogger();
        $logger2 = $manager->channel('single')->getLogger();

        $this->assertSame($logger1, $logger2);
    }
}
