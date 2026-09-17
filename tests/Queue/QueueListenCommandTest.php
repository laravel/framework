<?php

namespace Illuminate\Tests\Queue;

use Illuminate\Config\Repository;
use Illuminate\Console\Application as ConsoleApplication;
use Illuminate\Events\Dispatcher;
use Illuminate\Foundation\Application;
use Illuminate\Queue\Console\ListenCommand;
use Illuminate\Queue\Listener;
use Illuminate\Queue\ListenerOptions;
use InvalidArgumentException;
use Mockery as m;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Process\Process;

class QueueListenCommandTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();

        parent::tearDown();
    }

    public function testListenRetainsItsExistingBehaviorWithoutWatch()
    {
        $listener = m::mock(Listener::class);
        $listener->shouldReceive('setOutputHandler')->once();
        $listener->shouldReceive('listen')->once()->with(null, 'default', m::on(function (ListenerOptions $options) {
            return $options->timeout === '60' && $options->rest === 2;
        }));

        $tester = $this->tester(new ListenCommand($listener));

        $this->assertSame(0, $tester->execute(['--poll' => true, '--rest' => 2]));
    }

    #[DataProvider('invalidWatchConfiguration')]
    public function testWatchRequiresConfiguredPaths(array $config)
    {
        $tester = $this->tester(new ListenCommand(new Listener(__DIR__)), $config);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('List of directories / files to watch not found.');

        $tester->execute(['--watch' => true]);
    }

    public static function invalidWatchConfiguration()
    {
        return [[[]], [['watch' => null]], [['watch' => []]]];
    }

    public function testWatchRestartsWorkerOnChanges()
    {
        $command = $this->command();
        $watcher = m::mock(Process::class);
        $worker = m::mock(Process::class);
        $command->shouldReceive('startWatcher')->once()->andReturn($watcher);
        $command->shouldReceive('createWorkerProcess')->twice()->andReturn($worker);
        $command->shouldReceive('trap')->twice();
        $watcher->shouldReceive('isTerminated')->once()->andReturn(false);
        $watcher->shouldReceive('getIncrementalOutput')->once()->andReturn('File changed...');
        $worker->shouldReceive('start')->twice();
        $worker->shouldReceive('isTerminated')->twice()->andReturn(false);
        $worker->shouldReceive('stop')->once();
        $worker->shouldReceive('wait')->once();
        $worker->shouldReceive('getIncrementalOutput')->once()->andReturn('Job processed');
        $worker->shouldReceive('isRunning')->once()->andReturn(false);

        $tester = $this->tester($command);

        $this->assertSame(0, $tester->execute(['--watch' => true]));
        $this->assertStringContainsString('File changed. Restarting queue worker...', $tester->getDisplay());
        $this->assertStringContainsString('Job processed', $tester->getDisplay());
    }

    public function testWatchForwardsWorkerOptionsWithoutOnceOrProcessTimeout()
    {
        $command = $this->command();
        $command->shouldReceive('watch')->once()->andReturnUsing(function () use ($command) {
            $process = (fn () => $this->createWorkerProcess())->call($command);
            $line = $process->getCommandLine();

            foreach (['queue:work', 'redis', '--name=custom', '--queue=high,low', '--backoff=5', '--memory=256', '--sleep=1', '--rest=2', '--timeout=90', '--tries=3', '--env=testing', '--force'] as $argument) {
                $this->assertStringContainsString($argument, $line);
            }

            $this->assertStringNotContainsString('--once', $line);
            $this->assertStringNotContainsString('--watch', $line);
            $this->assertStringNotContainsString('--poll', $line);
            $this->assertNull($process->getTimeout());
            $this->assertSame(__DIR__, $process->getWorkingDirectory());

            return 0;
        });

        $this->tester($command)->execute([
            'connection' => 'redis', '--watch' => true, '--poll' => true,
            '--name' => 'custom', '--queue' => 'high,low', '--backoff' => 5,
            '--memory' => 256, '--sleep' => 1, '--rest' => 2, '--timeout' => 90,
            '--tries' => 3, '--env' => 'testing', '--force' => true,
        ]);
    }

    protected function command()
    {
        $command = m::mock(QueueListenCommandStub::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $command->__construct(new Listener(__DIR__));

        return $command;
    }

    protected function tester(ListenCommand $command, array $config = [])
    {
        $app = new Application(__DIR__);
        $app->instance('config', new Repository(['queue' => array_merge([
            'default' => 'redis',
            'connections' => ['redis' => ['queue' => 'default']],
        ], $config)]));

        $console = new ConsoleApplication($app, new Dispatcher($app), 'testing');
        $console->add($command);

        return new CommandTester($command);
    }
}

class QueueListenCommandStub extends ListenCommand
{
}
