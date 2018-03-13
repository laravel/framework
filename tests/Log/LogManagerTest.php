<?php

namespace Illuminate\Tests\Log;

use Illuminate\Log\Logger;
use Illuminate\Log\LogManager;
use Monolog\Logger as Monolog;
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

    public function testLogManagerCreatesConfiguredMonologHandler()
    {
        $config = $this->app['config'];
        $config->set('logging.channels.tester', [
            'driver' => 'monolog',
            'name' => 'foobar',
            'handler_type' => 'stream',
            'handler_params' => [
                'stream' => 'php://stderr',
                'level' => Monolog::NOTICE
            ]
        ]);

        // create logger with handler specified from configuration
        $manager = new LogManager($this->app);
        $logger = $manager->channel('tester');
        $handlers = $logger->getLogger()->getHandlers();

        $this->assertInstanceOf(Logger::class, $logger);
        $this->assertEquals('foobar', $logger->getName());
        $this->assertCount(1, $handlers);
        $this->assertEquals('php://stderr', $handlers[0]->getUrl());
        $this->assertEquals(Monolog::NOTICE, $handlers[0]->getLevel());
    }
}
