<?php

namespace Illuminate\Tests\Integration\Log;

use Monolog\Logger;
use Illuminate\Log\Channel;
use Monolog\Handler\TestHandler;
use Illuminate\Tests\Integration\IntegrationTest;

class LoggingTest extends IntegrationTest
{
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('logging', [
            'default' => 'test',
            'channels' => [
                'test' => [
                    'type' => TestChannel::class,
                ],
                'errors' => [
                    'type' => TestChannel::class,
                    'pipes' => 'level:error',
                ],
            ],
        ]);
    }

    public function testLoggingToDefaultChannel()
    {
        $this->app['log']->info('test');

        $this->assertTrue($this->app['log']->to('test')->handler->hasInfoThatContains('test'));
    }

    public function testLoggingToSpecifiedChannel()
    {
        $this->app['config']->set('logging.channels.another-test', [
            'type' => TestChannel::class,
        ]);

        $this->app['log']->to('another-test')->info('Another test');

        $this->assertTrue($this->app['log']->to('another-test')->handler->hasInfoThatContains('Another test'));
        $this->assertFalse($this->app['log']->to('test')->handler->hasInfoRecords());
    }

    public function testLoggingToMultipleDefaultChannels()
    {
        $this->app['config']->set('logging.channels.another-test', [
            'type' => TestChannel::class,
        ]);
        $this->app['config']->set('logging.default', ['test', 'another-test']);

        $this->app['log']->info('Double test');

        $this->assertTrue($this->app['log']->to('test')->handler->hasInfoThatContains('Double test'));
        $this->assertTrue($this->app['log']->to('another-test')->handler->hasInfoThatContains('Double test'));
    }

    public function testMiddlewareIsExecuted()
    {
        $this->app['log']->to('errors')->info("This won't be logged");
        $this->assertFalse($this->app['log']->to('errors')->handler->hasInfoRecords());
        $this->app['log']->to('errors')->error('This is an error and will be logged');
        $this->assertTrue($this->app['log']->to('errors')->handler->hasErrorThatContains('This is an error and will be logged'));
    }

    public function testLoggerCanListenToEvent()
    {
        $this->app['log']->event('some.event', 'info')->to('test');

        event('some.event', ['foo', 'bar']);

        $this->assertTrue($this->app['log']->to('test')->handler->hasRecordThatPasses(function ($record) {
            return $record['message'] === 'Event triggered: some.event' &&
                $record['context'] == ['event_data' => ['foo', 'bar']];
        }, Logger::toMonologLevel('info')));
    }

    public function testLoggerCanLogObjectEventsAndExcludeNonPublicProperties()
    {
        $this->app['log']->event(TestEvent::class, 'info')->to('test');

        event(new TestEvent('bar'));

        $this->assertTrue($this->app['log']->to('test')->handler->hasRecordThatPasses(function ($record) {
            return $record['message'] === 'Event triggered: '.TestEvent::class &&
                $record['context'] == ['event_data' => ['foo' => 'bar']];
        }, Logger::toMonologLevel('info')));
    }
}

class TestChannel extends Channel
{
    public $handler;

    public function prepare(array $options = [])
    {
        $this->writer->pushHandler($this->handler = new TestHandler);
    }
}

class TestEvent
{
    public $foo;
    protected $bar = 'foo';

    public function __construct($foo)
    {
        $this->foo = $foo;
    }
}
