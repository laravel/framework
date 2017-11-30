<?php

namespace Illuminate\Tests\Log;

use Mockery;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Illuminate\Contracts\Container\Container;

class ChannelTest extends TestCase
{
    public function tearDown()
    {
        Mockery::close();
    }

    public function testItProxiesCallsToMonolog()
    {
        $channel = new TestChannel($app = Mockery::mock(Container::class), $log = Mockery::mock(Logger::class));
        $app->shouldReceive('make');
        $log->shouldReceive('log')->with('info', 'Test', [])->once();

        $channel->info('Test');
    }
}

class TestChannel extends \Illuminate\Log\Channel
{
    public function prepare(array $options = [])
    {
        //
    }
}
