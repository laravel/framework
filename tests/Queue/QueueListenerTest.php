<?php

namespace Illuminate\Tests\Queue;

use Illuminate\Queue\Listener;
use Illuminate\Queue\ListenerOptions;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

use function Illuminate\Support\artisan_binary;
use function Illuminate\Support\php_binary;

class QueueListenerTest extends TestCase
{
    protected function tearDown(): void
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
        $options->backoff = 1;
        $options->memory = 2;
        $options->timeout = 3;
        $process = $listener->makeProcess('connection', 'queue', $options);
        $escape = '\\' === DIRECTORY_SEPARATOR ? '' : '\'';

        $artisanBinary = artisan_binary();

        $this->assertInstanceOf(Process::class, $process);
        $this->assertEquals(__DIR__, $process->getWorkingDirectory());
        $this->assertEquals(3, $process->getTimeout());
        $this->assertEquals($escape.php_binary().$escape." {$escape}{$artisanBinary}{$escape} {$escape}queue:work{$escape} {$escape}connection{$escape} {$escape}--once{$escape} {$escape}--name=default{$escape} {$escape}--queue=queue{$escape} {$escape}--backoff=1{$escape} {$escape}--memory=2{$escape} {$escape}--sleep=3{$escape} {$escape}--tries=1{$escape}", $process->getCommandLine());
    }

    public function testMakeProcessCorrectlyFormatsCommandLineWithAnEnvironmentSpecified()
    {
        $listener = new Listener(__DIR__);
        $options = new ListenerOptions('default', 'test');
        $options->backoff = 1;
        $options->memory = 2;
        $options->timeout = 3;
        $process = $listener->makeProcess('connection', 'queue', $options);
        $escape = '\\' === DIRECTORY_SEPARATOR ? '' : '\'';

        $artisanBinary = artisan_binary();

        $this->assertInstanceOf(Process::class, $process);
        $this->assertEquals(__DIR__, $process->getWorkingDirectory());
        $this->assertEquals(3, $process->getTimeout());
        $this->assertEquals($escape.php_binary().$escape." {$escape}{$artisanBinary}{$escape} {$escape}queue:work{$escape} {$escape}connection{$escape} {$escape}--once{$escape} {$escape}--name=default{$escape} {$escape}--queue=queue{$escape} {$escape}--backoff=1{$escape} {$escape}--memory=2{$escape} {$escape}--sleep=3{$escape} {$escape}--tries=1{$escape} {$escape}--env=test{$escape}", $process->getCommandLine());
    }

    public function testMakeProcessCorrectlyFormatsCommandLineWhenTheConnectionIsNotSpecified()
    {
        $listener = new Listener(__DIR__);
        $options = new ListenerOptions('default', 'test');
        $options->backoff = 1;
        $options->memory = 2;
        $options->timeout = 3;
        $process = $listener->makeProcess(null, 'queue', $options);
        $escape = '\\' === DIRECTORY_SEPARATOR ? '' : '\'';

        $artisanBinary = artisan_binary();

        $this->assertInstanceOf(Process::class, $process);
        $this->assertEquals(__DIR__, $process->getWorkingDirectory());
        $this->assertEquals(3, $process->getTimeout());
        $this->assertEquals($escape.php_binary().$escape." {$escape}{$artisanBinary}{$escape} {$escape}queue:work{$escape} {$escape}--once{$escape} {$escape}--name=default{$escape} {$escape}--queue=queue{$escape} {$escape}--backoff=1{$escape} {$escape}--memory=2{$escape} {$escape}--sleep=3{$escape} {$escape}--tries=1{$escape} {$escape}--env=test{$escape}", $process->getCommandLine());
    }
}
