<?php

namespace Illuminate\Tests\Queue;

use Illuminate\Foundation\Application;
use Illuminate\Queue\Console\FlushFailedCommand;
use Illuminate\Queue\Failed\FailedJobProviderInterface;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

class FlushFailedCommandTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testFlushWithConfirmationRequired()
    {
        $command = new FlushFailedCommandTestStub;
        $failedProvider = m::mock(FailedJobProviderInterface::class);

        // Create a mock application that simulates development environment
        $app = m::mock(Application::class);
        $app->shouldReceive('environment')->andReturn('development');
        $app->shouldReceive('offsetGet')->with('queue.failer')->andReturn($failedProvider);

        $command->setLaravel($app);

        $failedProvider->shouldReceive('flush')->once()->with(null);

        // Test with --force flag to bypass confirmation
        $this->runCommand($command, ['--force' => true]);
    }

    public function testFlushWithHoursOption()
    {
        $command = new FlushFailedCommandTestStub;
        $failedProvider = m::mock(FailedJobProviderInterface::class);

        // Create a mock application that simulates development environment
        $app = m::mock(Application::class);
        $app->shouldReceive('environment')->andReturn('development');
        $app->shouldReceive('offsetGet')->with('queue.failer')->andReturn($failedProvider);

        $command->setLaravel($app);

        $failedProvider->shouldReceive('flush')->once()->with('24');

        // Test with --force flag and --hours option
        $this->runCommand($command, ['--force' => true, '--hours' => '24']);
    }

    protected function runCommand($command, $input = [])
    {
        return $command->run(new ArrayInput($input), new NullOutput);
    }
}

class FlushFailedCommandTestStub extends FlushFailedCommand
{
    public function call($command, array $arguments = [])
    {
        return 0;
    }
}
