<?php

namespace Illuminate\Tests\Console;

use Illuminate\Console\Application;
use Illuminate\Console\Command;
use Illuminate\Console\OutputStyle;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

class CommandTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testCallingClassCommandResolveCommandViaApplicationResolution()
    {
        $command = new Command();

        $application = m::mock(Application::class);
        $command->setLaravel($application);

        $input = new ArrayInput([]);
        $output = new NullOutput();
        $application->shouldReceive('make')->with(OutputStyle::class, ['input' => $input, 'output' => $output])->andReturn(m::mock(OutputStyle::class));

        $application->shouldReceive('call')->with([$command, 'handle'])->andReturnUsing(function () use ($command, $application) {
            $commandCalled = m::mock(Command::class);

            $application->shouldReceive('make')->once()->with(Command::class)->andReturn($commandCalled);

            $commandCalled->shouldReceive('setApplication')->once()->with(null);
            $commandCalled->shouldReceive('setLaravel')->once()->with($application);
            $commandCalled->shouldReceive('run')->once();

            $command->call(Command::class);
        });

        $command->run($input, $output);
    }
}
