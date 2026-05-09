<?php

namespace Illuminate\Tests\Queue;

use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Foundation\Application;
use Illuminate\Queue\Console\ListenCommand;
use Illuminate\Queue\Listener;
use Illuminate\Queue\ListenerOptions;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\NullOutput;

class QueueListenCommandTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    protected function createApplication(array $queueConfig = []): Application
    {
        $app = new Application;
        $app['config'] = new ConfigRepository([
            'queue' => array_merge([
                'default' => 'redis',
                'connections' => [
                    'redis' => ['driver' => 'redis', 'queue' => 'default'],
                    'sqs' => ['driver' => 'sqs', 'queue' => 'sqs-queue'],
                ],
            ], $queueConfig),
        ]);

        return $app;
    }

    protected function makeListener(): Listener
    {
        $listener = m::mock(Listener::class);
        $listener->shouldReceive('setOutputHandler')->once();

        return $listener;
    }

    protected function runCommand(ListenCommand $command, array $input = [], $output = null): void
    {
        // The --env option is normally added by the Artisan console application globally.
        $command->getDefinition()->addOption(
            new InputOption('--env', null, InputOption::VALUE_OPTIONAL, 'The environment the command should run under')
        );

        $command->run(new ArrayInput($input), $output ?? new NullOutput);
    }

    public function testHandleCallsListenerWithExplicitConnection()
    {
        $listener = $this->makeListener();
        $listener->shouldReceive('listen')
            ->once()
            ->with('redis', 'default', m::type(ListenerOptions::class));

        $command = new ListenCommand($listener);
        $command->setLaravel($this->createApplication());
        $this->runCommand($command, ['connection' => 'redis']);
    }

    public function testHandleUsesDefaultConnectionFromConfigWhenNotSpecified()
    {
        $listener = $this->makeListener();
        $listener->shouldReceive('listen')
            ->once()
            ->with(null, 'default', m::type(ListenerOptions::class));

        $command = new ListenCommand($listener);
        $command->setLaravel($this->createApplication());
        $this->runCommand($command);
    }

    public function testHandleResolvesQueueFromConnectionConfig()
    {
        $listener = $this->makeListener();
        $listener->shouldReceive('listen')
            ->once()
            ->with('sqs', 'sqs-queue', m::type(ListenerOptions::class));

        $command = new ListenCommand($listener);
        $command->setLaravel($this->createApplication());
        $this->runCommand($command, ['connection' => 'sqs']);
    }

    public function testHandleUsesQueueOptionOverConnectionConfig()
    {
        $listener = $this->makeListener();
        $listener->shouldReceive('listen')
            ->once()
            ->with('sqs', 'custom-queue', m::type(ListenerOptions::class));

        $command = new ListenCommand($listener);
        $command->setLaravel($this->createApplication());
        $this->runCommand($command, ['connection' => 'sqs', '--queue' => 'custom-queue']);
    }

    public function testHandleDefaultsQueueToDefaultWhenNotInConnectionConfig()
    {
        $listener = $this->makeListener();
        $listener->shouldReceive('listen')
            ->once()
            ->with('database', 'default', m::type(ListenerOptions::class));

        $app = $this->createApplication([
            'connections' => ['database' => ['driver' => 'database']],
        ]);

        $command = new ListenCommand($listener);
        $command->setLaravel($app);
        $this->runCommand($command, ['connection' => 'database']);
    }

    public function testGatherOptionsBuildsListenerOptionsWithCommandOptions()
    {
        $capturedOptions = null;

        $listener = $this->makeListener();
        $listener->shouldReceive('listen')
            ->once()
            ->with(m::any(), m::any(), m::on(function (ListenerOptions $options) use (&$capturedOptions) {
                $capturedOptions = $options;

                return true;
            }));

        $command = new ListenCommand($listener);
        $command->setLaravel($this->createApplication());
        $this->runCommand($command, [
            '--name' => 'my-worker',
            '--backoff' => '5',
            '--memory' => '256',
            '--timeout' => '120',
            '--sleep' => '10',
            '--tries' => '3',
            '--rest' => '2',
            '--force' => true,
        ]);

        $this->assertInstanceOf(ListenerOptions::class, $capturedOptions);
        $this->assertSame('my-worker', $capturedOptions->name);
        $this->assertSame('5', $capturedOptions->backoff);
        $this->assertSame('256', $capturedOptions->memory);
        $this->assertSame('120', $capturedOptions->timeout);
        $this->assertSame('10', $capturedOptions->sleep);
        $this->assertSame('3', $capturedOptions->maxTries);
        $this->assertSame('2', $capturedOptions->rest);
        $this->assertTrue($capturedOptions->force);
    }

    public function testGatherOptionsUsesBackoffWhenAvailable()
    {
        $capturedOptions = null;

        $listener = $this->makeListener();
        $listener->shouldReceive('listen')
            ->once()
            ->with(m::any(), m::any(), m::on(function (ListenerOptions $options) use (&$capturedOptions) {
                $capturedOptions = $options;

                return true;
            }));

        $command = new ListenCommand($listener);
        $command->setLaravel($this->createApplication());
        $this->runCommand($command, ['--backoff' => '7']);

        $this->assertSame('7', $capturedOptions->backoff);
    }

    public function testOutputHandlerWritesToCommandOutput()
    {
        $capturedHandler = null;

        $listener = m::mock(Listener::class);
        $listener->shouldReceive('setOutputHandler')
            ->once()
            ->with(m::on(function (callable $handler) use (&$capturedHandler) {
                $capturedHandler = $handler;

                return true;
            }));
        $listener->shouldReceive('listen')->once();

        $buffered = new BufferedOutput;

        $command = new ListenCommand($listener);
        $command->setLaravel($this->createApplication());
        $this->runCommand($command, [], $buffered);

        $this->assertIsCallable($capturedHandler);
        $capturedHandler(null, 'hello from listener');

        $this->assertStringContainsString('hello from listener', $buffered->fetch());
    }
}
