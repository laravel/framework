<?php

use Illuminate\Log\Channel;
use Illuminate\Log\LogManager;
use PHPUnit\Framework\TestCase;
use Illuminate\Contracts\Events\Dispatcher;

class LogManagerTest extends TestCase
{
    public function tearDown()
    {
        Mockery::close();
    }

    public function testChannelsCanBeResolved()
    {
        $manager = new LogManager(Mockery::mock(Dispatcher::class));
        $manager->registerChannel('test', $channel = Mockery::mock(Channel::class));

        $this->assertSame($channel, $manager->to('test'));
    }

    /**
     * @expectedException \Illuminate\Log\ChannelResolutionException
     * @expectedExceptionMessage The log channel test is not registered
     */
    public function testAnExceptionIsThrownIfTheChannelCannotBeResolved()
    {
        $manager = new LogManager(Mockery::mock(Dispatcher::class));

        $manager->to('test');
    }

    public function testLogCallsAreProxiedToAllDefaultChannels()
    {
        $manager = new LogManager(Mockery::mock(Dispatcher::class));

        $manager->registerChannel('foo', $fooChannel = Mockery::mock(Channel::class));
        $manager->registerChannel('bar', $barChannel = Mockery::mock(Channel::class));
        $manager->registerChannel('baz', $bazChannel = Mockery::mock(Channel::class));

        $manager->setDefaultChannels(['foo', 'bar']);
        $bazChannel->shouldNotReceive('info');
        $fooChannel->shouldReceive('info')->once();
        $barChannel->shouldReceive('info')->once();

        $manager->info('foo bar but not baz');
    }
}
