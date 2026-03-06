<?php

namespace Illuminate\Tests\Log;

use Illuminate\Log\Logger;
use Monolog\Handler\TestHandler;
use Monolog\Logger as Monolog;
use Orchestra\Testbench\TestCase;

class LogSamplingTest extends TestCase
{
    private function makeLogger(): array
    {
        $handler = new TestHandler;
        $monolog = new Monolog('test', [$handler]);
        $logger = new Logger($monolog);

        return [$logger, $handler];
    }

    public function testSampleAtZeroDropsAllMessages()
    {
        [$logger, $handler] = $this->makeLogger();

        for ($i = 0; $i < 100; $i++) {
            $logger->sample(0.0)->info('test');
        }

        $this->assertCount(0, $handler->getRecords());
    }

    public function testSampleAtOnePassesAllMessages()
    {
        [$logger, $handler] = $this->makeLogger();

        for ($i = 0; $i < 100; $i++) {
            $logger->sample(1.0)->info('test');
        }

        $this->assertCount(100, $handler->getRecords());
    }

    public function testSampleAtPartialRateSamplesStatistically()
    {
        [$logger, $handler] = $this->makeLogger();

        for ($i = 0; $i < 10000; $i++) {
            $logger->sample(0.5)->info('test');
        }

        $count = count($handler->getRecords());

        $this->assertGreaterThan(1000, $count);
        $this->assertLessThan(9000, $count);
    }

    public function testSampleRateResetsAfterEachMessage()
    {
        [$logger, $handler] = $this->makeLogger();

        // Sampled message
        $logger->sample(0.0)->info('should be dropped');
        $this->assertCount(0, $handler->getRecords());

        // Next message without sample() should always log
        $logger->info('should be logged');
        $this->assertCount(1, $handler->getRecords());
    }

    public function testSampleThrowsWhenRateAboveOne()
    {
        [$logger] = $this->makeLogger();

        $this->expectException(\InvalidArgumentException::class);
        $logger->sample(1.5);
    }

    public function testSampleThrowsWhenRateBelowZero()
    {
        [$logger] = $this->makeLogger();

        $this->expectException(\InvalidArgumentException::class);
        $logger->sample(-0.5);
    }

    public function testSampleWorksWithAllLogLevels()
    {
        [$logger, $handler] = $this->makeLogger();

        $logger->sample(1.0)->debug('d');
        $logger->sample(1.0)->info('i');
        $logger->sample(1.0)->notice('n');
        $logger->sample(1.0)->warning('w');
        $logger->sample(1.0)->error('e');
        $logger->sample(1.0)->critical('c');
        $logger->sample(1.0)->alert('a');
        $logger->sample(1.0)->emergency('em');

        $this->assertCount(8, $handler->getRecords());
    }

    public function testSampleWorksWithWriteMethod()
    {
        [$logger, $handler] = $this->makeLogger();

        $logger->sample(0.0)->write('info', 'should be dropped');
        $this->assertCount(0, $handler->getRecords());

        $logger->sample(1.0)->write('info', 'should be logged');
        $this->assertCount(1, $handler->getRecords());
    }
}
