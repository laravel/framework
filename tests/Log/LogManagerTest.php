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

        $this->assertEquals(spl_object_id($logger1), spl_object_id($logger2));
    }
}
