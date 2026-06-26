<?php

namespace Illuminate\Tests\Console;

use Illuminate\Console\Application;
use Illuminate\Console\Attributes\Aliases;
use Illuminate\Console\Attributes\Help;
use Illuminate\Console\Attributes\Hidden;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Attributes\Usage;
use Illuminate\Console\Command;
use Illuminate\Console\CommandInput;
use Illuminate\Console\OutputStyle;
use Illuminate\Console\View\Components\Factory;
use Illuminate\Support\Carbon;
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
        $application->shouldReceive('runningUnitTests')->andReturn(true);

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

    public function testGettingCommandInputAsFluentData()
    {
        $command = new class extends Command
        {
            public function handle()
            {
            }

            protected function getArguments()
            {
                return [
                    ['type', InputArgument::OPTIONAL, 'a backed enum argument'],
                    ['when', InputArgument::OPTIONAL, 'a date argument'],
                    ['role', InputArgument::OPTIONAL, 'a colliding argument'],
                ];
            }

            protected function getOptions()
            {
                return [
                    ['limit', null, InputOption::VALUE_OPTIONAL, 'an integer option'],
                    ['role', null, InputOption::VALUE_OPTIONAL, 'a colliding option'],
                ];
            }
        };

        $application = app();
        $command->setLaravel($application);

        $input = new ArrayInput([
            'type' => 'foo',
            'when' => '2026-06-26',
            'role' => 'admin',
            '--limit' => '5',
            '--role' => 'user',
        ]);
        $output = new NullOutput;

        $command->run($input, $output);

        $commandInput = $command->input();

        $this->assertInstanceOf(CommandInput::class, $commandInput);
        $this->assertSame(CommandInputType::Foo, $commandInput->enum('type', CommandInputType::class));
        $this->assertInstanceOf(Carbon::class, $commandInput->date('when'));
        $this->assertSame('2026-06-26', $commandInput->date('when')->format('Y-m-d'));
        $this->assertSame(5, $commandInput->integer('limit'));
        $this->assertSame('user', $commandInput->all()['role']);
        $this->assertSame('user', (string) $commandInput->string('role'));
        $this->assertSame('admin', $commandInput->arguments()['role']);
        $this->assertSame('user', $commandInput->options()['role']);
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

    public function testAliasesProperty()
    {
        $command = new class extends Command
        {
            protected $name = 'foo:bar';

            protected $aliases = ['bar:baz', 'baz:qux'];
        };

        $this->assertSame(['bar:baz', 'baz:qux'], $command->getAliases());
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

    public function testSignatureAttributeCanSetAliases()
    {
        $command = new SignatureWithAliasesCommand;

        $this->assertSame('foo:bar', $command->getName());
        $this->assertSame(['bar:baz', 'baz:qux'], $command->getAliases());
    }

    public function testAliasesAttributeCanSetAliases()
    {
        $command = new AliasesAttributeCommand;

        $this->assertSame('foo:bar', $command->getName());
        $this->assertSame(['bar:baz', 'baz:qux'], $command->getAliases());
    }

    public function testAliasesAttributeOverridesSignatureAliases()
    {
        $command = new AliasesAttributeOverridesSignatureCommand;

        $this->assertSame('foo:bar', $command->getName());
        $this->assertSame(['override:alias'], $command->getAliases());
    }

    public function testHiddenAttributeHidesCommand()
    {
        $command = new HiddenCommand;

        $this->assertTrue($command->isHidden());
    }

    public function testHelpAttributeCanSetHelp()
    {
        $command = new HelpCommand;

        $this->assertSame('Extended help text.', $command->getHelp());
    }

    public function testUsageAttributeCanSetUsages()
    {
        $command = new UsageCommand;

        $this->assertSame(['foo:bar 1', 'foo:bar 1 --force'], $command->getUsages());
    }
}

enum CommandInputType: string
{
    case Foo = 'foo';
    case Bar = 'bar';
}

#[Signature('foo:bar', aliases: ['bar:baz', 'baz:qux'])]
class SignatureWithAliasesCommand extends Command
{
    public function handle()
    {
    }
}

#[Signature('foo:bar')]
#[Hidden]
class HiddenCommand extends Command
{
    public function handle()
    {
    }
}

#[Signature('foo:bar')]
#[Help('Extended help text.')]
class HelpCommand extends Command
{
    public function handle()
    {
    }
}

#[Signature('foo:bar {user}')]
#[Usage('foo:bar 1')]
#[Usage('foo:bar 1 --force')]
class UsageCommand extends Command
{
    public function handle()
    {
    }
}

#[Signature('foo:bar')]
#[Aliases(['bar:baz', 'baz:qux'])]
class AliasesAttributeCommand extends Command
{
    public function handle()
    {
    }
}

#[Signature('foo:bar', aliases: ['ignored:alias'])]
#[Aliases(['override:alias'])]
class AliasesAttributeOverridesSignatureCommand extends Command
{
    public function handle()
    {
    }
}
