<?php

namespace Illuminate\Tests\Console;

use Illuminate\Console\Command;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

class CommandSignatureTest extends TestCase
{
    #[DataProvider('commands')]
    public function testCommandSignatureMatchesFixture(string $class, array $expected): void
    {
        $this->assertTrue(
            class_exists($class),
            "Command class [{$class}] no longer exists. Update tests/Console/Fixtures/command_signatures.php."
        );

        $command = $this->makeCommandWithoutDependencies($class);

        $this->assertSame($expected['name'], $command->getName(), "Command name changed for [{$class}].");
        $this->assertSame($expected['aliases'], $command->getAliases(), "Command aliases changed for [{$class}].");
        $this->assertSame($expected['hidden'], $command->isHidden(), "Command visibility changed for [{$class}].");

        $definition = $command->getDefinition();

        $arguments = [];

        foreach ($definition->getArguments() as $argument) {
            $arguments[] = [
                'name' => $argument->getName(),
                'mode' => $argument->isRequired() ? 'required' : 'optional',
                'isArray' => $argument->isArray(),
                'default' => $argument->getDefault(),
                'description' => $argument->getDescription(),
            ];
        }

        $options = [];

        foreach ($definition->getOptions() as $option) {
            $options[] = [
                'name' => $option->getName(),
                'shortcut' => $option->getShortcut(),
                'negatable' => $option->isNegatable(),
                'valueRequired' => $option->isValueRequired(),
                'valueOptional' => $option->isValueOptional(),
                'isArray' => $option->isArray(),
                'acceptValue' => $option->acceptValue(),
                'default' => $option->getDefault(),
                'description' => $option->getDescription(),
            ];
        }

        $this->assertSame($expected['arguments'], $arguments, "Command arguments changed for [{$class}].");
        $this->assertSame($expected['options'], $options, "Command options changed for [{$class}].");
    }

    public static function commands(): array
    {
        $commands = require __DIR__.'/Fixtures/command_signatures.php';

        $cases = [];

        foreach ($commands as $class => $expected) {
            $expected = [
                'aliases' => $expected['aliases'] ?? [],
                'hidden' => $expected['hidden'] ?? false,
                'arguments' => $expected['arguments'] ?? [],
                'options' => $expected['options'] ?? [],
                ...$expected,
            ];

            $cases[$class] = [$class, $expected];
        }

        return $cases;
    }

    protected function makeCommandWithoutDependencies(string $class): Command
    {
        $reflection = new ReflectionClass($class);

        $instance = $reflection->newInstanceWithoutConstructor();

        (new ReflectionMethod(Command::class, '__construct'))->invoke($instance);

        return $instance;
    }
}
