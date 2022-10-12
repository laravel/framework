<?php

namespace Illuminate\Tests\Console;

use Illuminate\Console\Application;
use Illuminate\Console\Command;
use Illuminate\Console\OutputStyle;
use Illuminate\Console\View\Components\Factory;
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
        $command = new class extends Command
        {
            public function handle()
            {
            }
        };

        $application = m::mock(Application::class);
        $command->setLaravel($application);

        $input = new ArrayInput([]);
        $output = new NullOutput;
        $outputStyle = m::mock(OutputStyle::class);
        $application->shouldReceive('make')->with(OutputStyle::class, ['input' => $input, 'output' => $output])->andReturn($outputStyle);
        $application->shouldReceive('make')->with(Factory::class, ['output' => $outputStyle])->andReturn(m::mock(Factory::class));

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
                    [
                        'name' => 'argument-three',
                        'description' => 'a third test argument',
                        'mode' => InputArgument::OPTIONAL,
                        'default' => 'third-argument-default',
                    ],
                ];
            }

            protected function getOptions()
            {
                return [
                    new InputOption('option-one', 'o', InputOption::VALUE_OPTIONAL, 'first test option'),
                    ['option-two', 't', InputOption::VALUE_REQUIRED, 'second test option'],
                    [
                        'name' => 'option-three',
                        'description' => 'a third test option',
                        'mode' => InputOption::VALUE_OPTIONAL,
                        'default' => 'third-option-default',
                    ],
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
        $output = new NullOutput;

        $command->run($input, $output);

        $this->assertSame('test-first-argument', $command->argument('argument-one'));
        $this->assertSame('test-second-argument', $command->argument('argument-two'));
        $this->assertSame('third-argument-default', $command->argument('argument-three'));
        $this->assertSame('test-first-option', $command->option('option-one'));
        $this->assertSame('test-second-option', $command->option('option-two'));
        $this->assertSame('third-option-default', $command->option('option-three'));
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

    public function testSetHidden()
    {
        $command = new class extends Command
        {
            public function parentIsHidden()
            {
                return parent::isHidden();
            }
        };

        $this->assertFalse($command->isHidden());
        $this->assertFalse($command->parentIsHidden());

        $command->setHidden(true);

        $this->assertTrue($command->isHidden());
        $this->assertTrue($command->parentIsHidden());
    }

    public function testHiddenProperty()
    {
        $command = new class extends Command
        {
            protected $hidden = true;

            public function parentIsHidden()
            {
                return parent::isHidden();
            }
        };

        $this->assertTrue($command->isHidden());
        $this->assertTrue($command->parentIsHidden());

        $command->setHidden(false);

        $this->assertFalse($command->isHidden());
        $this->assertFalse($command->parentIsHidden());
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
