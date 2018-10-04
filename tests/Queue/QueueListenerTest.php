<?php

namespace Illuminate\Tests\Queue;

use Mockery as m;
use Illuminate\Queue\Listener;
use PHPUnit\Framework\TestCase;
use Illuminate\Queue\ListenerOptions;
use Symfony\Component\Process\Process;

class QueueListenerTest extends TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testRunProcessCallsProcess()
    {
        $process = m::mock(Process::class)->makePartial();
        $process->shouldReceive('run')->once();
        $listener = m::mock(Listener::class)->makePartial();
        $listener->shouldReceive('memoryExceeded')->once()->with(1)->andReturn(false);

        $listener->runProcess($process, 1);
    }

    public function testListenerStopsWhenMemoryIsExceeded()
    {
        $process = m::mock(Process::class)->makePartial();
        $process->shouldReceive('run')->once();
        $listener = m::mock(Listener::class)->makePartial();
        $listener->shouldReceive('memoryExceeded')->once()->with(1)->andReturn(true);
        $listener->shouldReceive('stop')->once();

        $listener->runProcess($process, 1);
    }

    public function testMakeProcessCorrectlyFormatsCommandLine()
    {
        $listener = new Listener(__DIR__);
        $options = new ListenerOptions;
        $options->delay = 1;
        $options->memory = 2;
        $options->timeout = 3;
        $process = $listener->makeProcess('connection', 'queue', $options);
        $escape = '\\' === DIRECTORY_SEPARATOR ? '"' : '\'';

        $this->assertInstanceOf(Process::class, $process);
        $this->assertEquals(__DIR__, $process->getWorkingDirectory());
        $this->assertEquals(3, $process->getTimeout());
        $this->assertEquals($escape.PHP_BINARY.$escape." {$escape}artisan{$escape} queue:work {$escape}connection{$escape} --once --queue={$escape}queue{$escape} --delay=1 --memory=2 --sleep=3 --tries=0", $process->getCommandLine());
    }
}
