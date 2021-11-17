<?php

namespace Illuminate\Tests\Console;

use Illuminate\Console\Application;
use Illuminate\Console\Command;
use Illuminate\Console\OutputStyle;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Question\ChoiceQuestion;

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

    public function testGettingCommandArgumentsAndOptionsByClass()
    {
        $command = new class extends Command
        {
            public function handle()
            {
            }

            protected function getArguments()
            {
                return [
                    new InputArgument('argument-one', InputArgument::REQUIRED, 'first test argument'),
                    ['argument-two', InputArgument::OPTIONAL, 'a second test argument'],
                ];
            }

            protected function getOptions()
            {
                return [
                    new InputOption('option-one', 'o', InputOption::VALUE_OPTIONAL, 'first test option'),
                    ['option-two', 't', InputOption::VALUE_REQUIRED, 'second test option'],
                ];
            }
        };

        $application = app();
        $command->setLaravel($application);

        $input = new ArrayInput([
            'argument-one' => 'test-first-argument',
            'argument-two' => 'test-second-argument',
            '--option-one' => 'test-first-option',
            '--option-two' => 'test-second-option',
        ]);
        $output = new NullOutput();

        $command->run($input, $output);

        $this->assertSame('test-first-argument', $command->argument('argument-one'));
        $this->assertSame('test-second-argument', $command->argument('argument-two'));
        $this->assertSame('test-first-option', $command->option('option-one'));
        $this->assertSame('test-second-option', $command->option('option-two'));
    }

    public function testTheInputSetterOverwrite()
    {
        $input = m::mock(InputInterface::class);
        $input->shouldReceive('hasArgument')->once()->with('foo')->andReturn(false);

        $command = new Command;
        $command->setInput($input);

        $this->assertFalse($command->hasArgument('foo'));
    }

    public function testTheOutputSetterOverwrite()
    {
        $output = m::mock(OutputStyle::class);
        $output->shouldReceive('writeln')->once()->withArgs(function (...$args) {
            return $args[0] === '<info>foo</info>';
        });

        $command = new Command;
        $command->setOutput($output);

        $command->info('foo');
    }

    public function testChoiceIsSingleSelectByDefault()
    {
        $output = m::mock(OutputStyle::class);
        $output->shouldReceive('askQuestion')->once()->withArgs(function (ChoiceQuestion $question) {
            return $question->isMultiselect() === false;
        });

        $command = new Command;
        $command->setOutput($output);

        $command->choice('Do you need further help?', ['yes', 'no']);
    }

    public function testChoiceWithMultiselect()
    {
        $output = m::mock(OutputStyle::class);
        $output->shouldReceive('askQuestion')->once()->withArgs(function (ChoiceQuestion $question) {
            return $question->isMultiselect() === true;
        });

        $command = new Command;
        $command->setOutput($output);

        $command->choice('Select all that apply.', ['option-1', 'option-2', 'option-3'], null, null, true);
    }
}
