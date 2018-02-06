<?php

namespace Illuminate\Tests\Queue;

use Mockery as m;
use PHPUnit\Framework\TestCase;

class QueuePoolTest extends TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testRunProcessCallsProcess()
    {
        $process1 = m::mock('Symfony\Component\Process\Process')->makePartial();
        $process1->shouldReceive('start')->once();
        $process2 = m::mock('Symfony\Component\Process\Process')->makePartial();
        $process2->shouldReceive('start')->once();

        $pool = m::mock('Illuminate\Queue\Pool')->makePartial();
        $pool->shouldReceive('getProcesses')->once()->andReturn([$process1, $process2]);
        $pool->shouldReceive('memoryExceeded')->once()->with(1)->andReturn(false);

        $pool->runProcesses(1);
    }

    public function testPoolCanRestartWorker()
    {
        $process = m::mock('Symfony\Component\Process\Process')->makePartial();
        $process->shouldReceive('start')->once();
        $process->shouldReceive('isRunning')->twice()->andReturn(true, false);

        $pool = m::mock('Illuminate\Queue\Pool')->makePartial();
        $pool->shouldReceive('getProcesses')->twice()->andReturn([$process]);
        $pool->shouldReceive('memoryExceeded')->twice()->with(1)->andReturn(false);

        $pool->runProcesses(1);
        $pool->runProcesses(1);
    }

    public function testPoolStopsWhenMemoryIsExceeded()
    {
        $process = m::mock('Symfony\Component\Process\Process')->makePartial();
        $process->shouldReceive('start')->once();

        $pool = m::mock('Illuminate\Queue\Pool')->makePartial();
        $pool->shouldReceive('getProcesses')->once()->andReturn([$process]);
        $pool->shouldReceive('memoryExceeded')->once()->with(1)->andReturn(true);
        $pool->shouldReceive('stop')->once();

        $pool->runProcesses(1);
    }

    public function testMakeProcessCorrectlyFormatsCommandLine()
    {
        $pool = new \Illuminate\Queue\Pool(__DIR__);
        $options = new \Illuminate\Queue\PoolOption();
        $options->workers = 1;
        $options->delay = 1;
        $options->memory = 2;
        $options->timeout = 3;
        $processes = $pool->makeProcesses('connection', 'queue', $options);
        $escape = '\\' === DIRECTORY_SEPARATOR ? '"' : '\'';

        $this->assertCount(1, $processes);
        $process = array_first($processes);

        $this->assertInstanceOf('Symfony\Component\Process\Process', $process);
        $this->assertEquals(__DIR__, $process->getWorkingDirectory());
        $this->assertEquals(3, $process->getTimeout());
        $this->assertEquals($escape.PHP_BINARY.$escape." {$escape}artisan{$escape} queue:work {$escape}connection{$escape} --queue={$escape}queue{$escape} --delay=1 --memory=2 --sleep=3 --tries=0", $process->getCommandLine());
    }
}
