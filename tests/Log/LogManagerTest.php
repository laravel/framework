<?php

namespace Illuminate\Tests\Log;

use Illuminate\Log\Logger;
use Illuminate\Log\LogManager;
use Monolog\Handler\LogEntriesHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger as Monolog;
use Orchestra\Testbench\TestCase;
use ReflectionProperty;

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
        $config->set('logging.channels.nonbubblingstream', [
            'driver' => 'monolog',
            'name' => 'foobar',
            'handler_type' => 'stream',
            'handler_params' => [
                'stream' => 'php://stderr',
                'level' => Monolog::NOTICE,
                'bubble' => false
            ]
        ]);

        $manager = new LogManager($this->app);

        // create logger with handler specified from configuration
        $logger = $manager->channel('nonbubblingstream');
        $handlers = $logger->getLogger()->getHandlers();

        $this->assertInstanceOf(Logger::class, $logger);
        $this->assertEquals('foobar', $logger->getName());
        $this->assertCount(1, $handlers);
        $this->assertInstanceOf(StreamHandler::class, $handlers[0]);
        $this->assertEquals('php://stderr', $handlers[0]->getUrl());
        $this->assertEquals(Monolog::NOTICE, $handlers[0]->getLevel());
        $this->assertFalse($handlers[0]->getBubble());

        $config->set('logging.channels.logentries', [
            'driver' => 'monolog',
            'name' => 'le',
            'handler_type' => 'LogEntries',
            'handler_params' => [
                'token' => '123456789'
            ]
        ]);

        $logger = $manager->channel('logentries');
        $handlers = $logger->getLogger()->getHandlers();

        $logToken = new ReflectionProperty(get_class($handlers[0]), 'logToken');
        $logToken->setAccessible(true);

        $this->assertInstanceOf(LogEntriesHandler::class, $handlers[0]);
        $this->assertEquals('123456789', $logToken->getValue($handlers[0]));
    }
}
