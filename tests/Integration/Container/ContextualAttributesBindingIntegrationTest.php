<?php

namespace Illuminate\Tests\Integration\Container;

use Illuminate\Container\Attributes\Log;
use Illuminate\Support\Collection;
use Monolog\Handler\TestHandler;
use Monolog\LogRecord;
use Orchestra\Testbench\TestCase;
use Psr\Log\LoggerInterface;

class ContextualAttributesBindingIntegrationTest extends TestCase
{
    public function testLogAttributeCanSetName()
    {
        config(['logging.default' => 'testing']);
        config(['logging.channels' => [
            'testing' => [
                'driver' => 'monolog',
                'handler' => TestHandler::class,
            ],
        ]]);

        /** @var TestHandler $testHandler */
        $testHandler = resolve('log')->driver()->getLogger()->getHandlers()[0];

        $tester = resolve(LogAttributeTester::class);
        $tester->log('hello');
        $tester->logWithName('bye');

        $records = new Collection($testHandler->getRecords());

        $this->assertCount(2, $records);
        $this->assertSame('hello', $records->firstWhere(function (LogRecord $record) {
            return $record->channel === 'testing';
        })->message);
        $this->assertSame('bye', $records->firstWhere(function (LogRecord $record) {
            return $record->channel === 'look-ma-a-channel-name';
        })->message);
    }
}

class LogAttributeTester
{
    public function __construct(
        #[Log('testing')]
        public LoggerInterface $logger,
        #[Log('testing', 'look-ma-a-channel-name')]
        public LoggerInterface $loggerWithName
    ) {
    }

    public function log(string $line)
    {
        $this->logger->info($line);
    }

    public function logWithName(string $line)
    {
        $this->loggerWithName->info($line);
    }
}
