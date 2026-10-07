<?php

namespace Illuminate\Tests\Console;

use Illuminate\Console\Attributes\Aliases;
use Illuminate\Console\Attributes\Help;
use Illuminate\Console\Attributes\Hidden;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Attributes\Usage;
use Illuminate\Console\Command;
use Illuminate\Console\CommandInput;
use Illuminate\Console\OutputStyle;
use Illuminate\Console\View\Components\Factory;
use Illuminate\Foundation\Application as FoundationApplication;
use Illuminate\Support\Carbon;
use Illuminate\Tests\Console\Concerns\CreatesAnsweredOutputStyles;
use JMac\Testing\Double;
use JMac\Testing\Matching\Argument;
use Laravel\Prompts\Prompt;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\NullOutput;

class CommandTest extends TestCase
{
    use CreatesAnsweredOutputStyles;

    protected function tearDown(): void
    {
        Prompt::setOutput(new NullOutput);
    }

    public function testCallingClassCommandResolveCommandViaApplicationResolution()
    {
        $command = new class extends Command
        {
            public function handle()
            {
            }
        };

        $application = Double::for(FoundationApplication::class, override: true);
        $laravel = $application->instance();
        $command->setLaravel($laravel);

        $input = new ArrayInput([]);
        $output = new NullOutput;
        $outputStyle = new OutputStyle($input, $output);
        $application->expects('make')->with(OutputStyle::class, ['input' => $input, 'output' => $output])->returns($outputStyle);
        $application->expects('make')->with(Factory::class, ['output' => $outputStyle])->returns(new Factory($outputStyle));

        $application->expects('call')->with([$command, 'handle'])->resolves(function () use ($command, $application, $laravel) {
            $commandCalled = Double::for(Command::class);

            $application->expects('make')->with(Command::class)->returns($commandCalled);

            $commandCalled->expects('setApplication')->with(null);
            $commandCalled->expects('setLaravel')->with($laravel);
            $commandCalled->expects('run');

            $command->call(Command::class);
        });
        $application->allows('runningUnitTests')->returns(true);

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

        $application = new FoundationApplication(__DIR__);
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

        $application = new FoundationApplication;
        $application['env'] = 'testing';
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
        $this->assertSame('admin', $commandInput->all()['role']);
        $this->assertSame('admin', $command->input('role'));
        $this->assertSame('fallback', $command->input('missing', 'fallback'));
        $this->assertSame('admin', (string) $commandInput->string('role'));
        $this->assertSame('admin', $commandInput->arguments()['role']);
        $this->assertSame('user', $commandInput->options()['role']);
    }

    public function testTheInputSetterOverwrite()
    {
        $command = new Command;
        $command->setInput(new ArrayInput([]));

        $this->assertFalse($command->hasArgument('foo'));
    }

    public function testTheOutputSetterOverwrite()
    {
        $output = Double::for(OutputStyle::class);
        $output->expects('writeln')->with('<info>foo</info>', Argument::remaining());

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
        $command = new Command;
        $command->setOutput($this->outputStyleWithAnswer('yes'));

        $answer = $command->choice('Do you need further help?', ['yes', 'no']);

        $this->assertSame('yes', $answer);
    }

    public function testChoiceWithMultiselect()
    {
        $command = new Command;
        $command->setOutput($this->outputStyleWithAnswer('option-1,option-2'));

        $answer = $command->choice('Select all that apply.', ['option-1', 'option-2', 'option-3'], null, null, true);

        $this->assertSame(['option-1', 'option-2'], $answer);
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
