<?php

namespace Illuminate\Tests\Console;

use Illuminate\Console\Attributes\Argument;
use Illuminate\Console\Attributes\Option;
use Illuminate\Console\Command;
use Illuminate\Tests\Console\fixtures\AttributeCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

if (PHP_VERSION_ID >= 80100) {
    include 'Enums.php';
}

class CommandAttributesTest extends TestCase
{
    public function testAttributeWillBeUsed()
    {
        $command = new AttributeCommand();
        $command = $this->callCommand($command);

        $this->assertSame('test:basic', $command->getName());
        $this->assertSame('Basic Command description!', $command->getDescription());
        $this->assertSame('Some Help.', $command->getHelp());
        $this->assertTrue($command->isHidden());
        $this->assertSame(['alias:basic'], $command->getAliases());
    }

    public function testArgumentsWillBeRegisteredWithAttributeSyntax()
    {
        $command = new class extends Command
        {
            protected $name = 'test';

            #[Argument]
            public string $requiredArgument;

            #[Argument]
            public ?string $optionalArgument;

            #[Argument]
            public string $defaultArgument = 'default_value';

            public function handle()
            {
            }
        };

        $definition = $command->getDefinition();

        $command = $this->callCommand($command, [
            'requiredArgument' => 'Argument_Required',
            'optionalArgument' => 'Argument_Optional',
            'defaultArgument' => 'Argument_Default',
        ]);

        $this->assertTrue($definition->getArgument('requiredArgument')->isRequired());
        $this->assertSame('Argument_Required', $command->requiredArgument);

        $this->assertFalse($definition->getArgument('optionalArgument')->isRequired());
        $this->assertSame('Argument_Optional', $command->optionalArgument);

        $this->assertSame('default_value', $definition->getArgument('defaultArgument')->getDefault());
        $this->assertSame('Argument_Default', $command->defaultArgument);
    }

    public function testArrayArgumentsWillBeRegisteredWithAttributeSyntax()
    {
        $commandRequired = new class extends Command
        {
            protected $name = 'test';

            #[Argument]
            public array $arrayArgument;

            public function handle()
            {
            }
        };

        $commandOptional = new class extends Command {
            protected $name = 'test';

            #[Argument]
            public ?array $optionalArrayArgument;

            public function handle()
            {
            }
        };

        $commandDefault = new class extends Command
        {
            protected $name = 'test';

            #[Argument]
            public array $defaultArrayArgument = ['Value A', 'Value B'];

            public function handle()
            {
            }
        };

        $commandRequired = $this->callCommand($commandRequired, [
            'arrayArgument' => ['Array_Required'],
        ]);

        $definition = $commandRequired->getDefinition();

        $this->assertTrue($definition->getArgument('arrayArgument')->isArray());
        $this->assertTrue($definition->getArgument('arrayArgument')->isRequired());
        $this->assertSame(['Array_Required'], $commandRequired->arrayArgument);

        $commandOptional = $this->callCommand($commandOptional, [
            'optionalArrayArgument' => ['Array_Optional'],
        ]);

        $definition = $commandOptional->getDefinition();

        $this->assertTrue($definition->getArgument('optionalArrayArgument')->isArray());
        $this->assertFalse($definition->getArgument('optionalArrayArgument')->isRequired());
        $this->assertSame(['Array_Optional'], $commandOptional->optionalArrayArgument);

        $commandDefault = $this->callCommand($commandDefault, [
            'defaultArrayArgument' => ['Array_Default'],
        ]);

        $definition = $commandDefault->getDefinition();

        $this->assertTrue($definition->getArgument('defaultArrayArgument')->isArray());
        $this->assertFalse($definition->getArgument('defaultArrayArgument')->isRequired());
        $this->assertSame(['Value A', 'Value B'], $definition->getArgument('defaultArrayArgument')->getDefault());
        $this->assertSame(['Array_Default'], $commandDefault->defaultArrayArgument);

        $commandDefault = $this->callCommand($commandDefault, []);
        $this->assertSame(['Value A', 'Value B'], $commandDefault->defaultArrayArgument);
    }

    public function testOptionsWillBeRegisteredWithAttributeSyntax()
    {
        $command = new class extends Command
        {
            protected $name = 'test';

            #[Option]
            public bool $option;

            #[Option]
            public string $optionWithValue;

            #[Option]
            public ?string $optionWithNullableValue;

            #[Option]
            public string $optionWithDefaultValue = 'default';

            public function handle()
            {
            }
        };

        $definition = $command->getDefinition();

        $command = $this->callCommand($command, [
            '--option' => true,
            '--optionWithValue' => 'Value A',
        ]);

        $this->assertFalse($definition->getOption('option')->isValueOptional());
        $this->assertTrue($command->option);

        $this->assertTrue($definition->getOption('optionWithValue')->isValueRequired());
        $this->assertSame('Value A', $command->optionWithValue);

        $this->assertTrue($definition->getOption('optionWithNullableValue')->isValueOptional());
        $this->assertNull($command->optionWithNullableValue);

        $command = $this->callCommand($command, [
            '--optionWithNullableValue' => 'Value B',
        ]);
        $this->assertSame('Value B', $command->optionWithNullableValue);

        $this->assertSame('default', $definition->getOption('optionWithDefaultValue')->getDefault());

        $command = $this->callCommand($command, [
            '--optionWithDefaultValue' => 'Value C',
        ]);
        $this->assertSame('Value C', $command->optionWithDefaultValue);
    }

    public function testArrayOptionsWillBeRegisteredWithAttributeSyntax()
    {
        $command = new class extends Command
        {
            protected $name = 'test';

            #[Option]
            public array $optionArray;

            #[Option]
            public array $optionDefaultArray = ['default1', 'default2'];

            public function handle()
            {
            }
        };

        $definition = $command->getDefinition();

        $command = $this->callCommand($command, []);
        $this->assertSame([], $command->optionArray);

        $command = $this->callCommand($command, [
            '--optionArray' => ['Value A', 'Value B'],
        ]);

        $this->assertTrue($definition->getOption('optionArray')->isArray());
        $this->assertSame(['Value A', 'Value B'], $command->optionArray);

        $this->assertTrue($definition->getOption('optionArray')->isArray());
        $this->assertTrue($definition->getOption('optionArray')->isValueOptional());
        $this->assertSame(['Value A', 'Value B'], $command->optionArray);

        $command = $this->callCommand($command, [
            '--optionDefaultArray' => ['Value C', 'Value D'],
        ]);

        $this->assertSame(['Value C', 'Value D'], $command->optionDefaultArray);
    }

    public function testInputMetaDataWillBeRegisteredWithAttributeSyntax()
    {
        $command = new class extends Command
        {
            protected $name = 'test';

            #[Argument(
                as: 'argumentAlias',
                description: 'Argument Description',
            )]
            public string $argument = '';

            #[Option(
                as: 'optionAlias',
                description: 'Option Description'
            )]
            public bool $option;

            public function handle()
            {
            }
        };

        $definition = $command->getDefinition();

        $this->assertSame('Argument Description', $definition->getArgument('argumentAlias')->getDescription());
        $this->assertSame('Option Description', $definition->getOption('optionAlias')->getDescription());

        $command = $this->callCommand($command, [
            'argumentAlias' => 'Value',
            '--optionAlias' => true,
        ]);

        $this->assertSame('Value', $command->argument);
        $this->assertSame(true, $command->option);
    }

    public function testOptionShortcutWillBeRegisteredWithAttributeSyntax()
    {
        $command = new class extends Command
        {
            protected $name = 'test';

            #[Option(
                shortcut: 'O'
            )]
            public string $option;

            public function handle(){}
        };

        $definition = $command->getDefinition();

        $this->assertSame('O', $definition->getOption('option')->getShortcut());

        $command = $this->callCommand($command, [
            '-O' => 'short',
        ]);

        $this->assertSame('short', $command->option);
    }

    public function testOptionNegatableWillBeRegisteredWithAttributeSyntax()
    {
        $command = new class extends Command
        {
            protected $name = 'test';

            #[Option(
                negatable: true
            )]
            public bool $option;

            public function handle()
            {
            }
        };

        $definition = $command->getDefinition();

        $this->assertTrue($definition->getOption('option')->isNegatable());

        $command = $this->callCommand($command, [
            '--option' => true,
        ]);

        $this->assertTrue($command->option);

        $command = $this->callCommand($command, [
            '--no-option' => true,
        ]);

        $this->assertFalse($command->option);
    }

    /**
     * @requires PHP >= 8.1
     */
    public function testArgumentEnumsWillBeCasted()
    {
        if (PHP_VERSION_ID <= 80100) {
            $this->markTestSkipped('Enum Casting test skipped caused by PHP version.');
            return;
        }

        $command = new class extends Command
        {
            protected $name = 'test';

            #[Argument]
            public Enum $enumArgument;

            #[Argument]
            public StringEnum $enumStringArgument;

            #[Argument]
            public IntEnum $enumIntArgument;

            #[Argument]
            public StringEnum $enumDefaultArgument = StringEnum::B;

            public function handle()
            {
            }
        };

        $command = $this->callCommand($command, [
            'enumArgument' => 'B',
            'enumStringArgument' => 'String B',
            'enumIntArgument' => 2,
        ]);

        $this->assertSame(Enum::B, $command->enumArgument);

        $this->assertSame(StringEnum::B, $command->enumStringArgument);

        $this->assertSame(IntEnum::B, $command->enumIntArgument);

        $this->assertSame(StringEnum::B, $command->enumDefaultArgument);
    }

    /**
     * @requires PHP >= 8.1
     */
    public function testOptionEnumsWillBeCasted()
    {
        if (PHP_VERSION_ID <= 80100) {
            $this->markTestSkipped('Enum Casting test skipped caused by PHP version.');
            return;
        }

        $command = new class extends Command
        {
            protected $name = 'test';

            #[Option]
            public Enum $enumOption;

            #[Option]
            public StringEnum $enumStringOption;

            #[Option]
            public IntEnum $enumIntOption;

            #[Option]
            public StringEnum $enumDefaultOption = StringEnum::B;

            public function handle()
            {
            }
        };

        $command = $this->callCommand($command, [
            '--enumOption' => 'B',
            '--enumStringOption' => 'String B',
            '--enumIntOption' => 2,
        ]);

        $this->assertSame(Enum::B, $command->enumOption);

        $this->assertSame(StringEnum::B, $command->enumStringOption);

        $this->assertSame(IntEnum::B, $command->enumIntOption);

        $this->assertSame(StringEnum::B, $command->enumDefaultOption);
    }

    protected function callCommand(Command $command, array $input = []): Command
    {
        $application = app();
        $command->setLaravel($application);

        $input = new ArrayInput($input);
        $output = new NullOutput();

        $command->run($input, $output);
        return $command;
    }
}
