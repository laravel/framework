<?php

namespace Illuminate\Tests\Integration\Console;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Tests\App\Console\Commands\DummyPromptsValidationCommand;
use Illuminate\Tests\App\Console\Commands\DummyPromptsWithLaravelRulesCommand;
use Illuminate\Tests\App\Console\Commands\DummyPromptsWithLaravelRulesCommandWithInlineMessagesAndAttributesCommand;
use Illuminate\Tests\App\Console\Commands\DummyPromptsWithLaravelRulesMessagesAndAttributesCommand;
use Orchestra\Testbench\TestCase;

class PromptsValidationTest extends TestCase
{
    protected function defineEnvironment($app)
    {
        $app[Kernel::class]->registerCommand(new DummyPromptsValidationCommand());
        $app[Kernel::class]->registerCommand(new DummyPromptsWithLaravelRulesCommand());
        $app[Kernel::class]->registerCommand(new DummyPromptsWithLaravelRulesMessagesAndAttributesCommand());
        $app[Kernel::class]->registerCommand(new DummyPromptsWithLaravelRulesCommandWithInlineMessagesAndAttributesCommand());
    }

    public function testValidationForPrompts(): void
    {
        $this
            ->artisan(DummyPromptsValidationCommand::class)
            ->expectsQuestion('What is your name?', '')
            ->expectsOutputToContain('Required!');
    }

    public function testValidationWithLaravelRulesAndNoCustomization(): void
    {
        $this
            ->artisan(DummyPromptsWithLaravelRulesCommand::class)
            ->expectsQuestion('What is your name?', '')
            ->expectsOutputToContain('The answer field is required.');
    }

    public function testValidationWithLaravelRulesInlineMessagesAndAttributes(): void
    {
        $this
            ->artisan(DummyPromptsWithLaravelRulesCommandWithInlineMessagesAndAttributesCommand::class)
            ->expectsQuestion('What is your name?', '')
            ->expectsOutputToContain('Your full name is mandatory.');
    }

    public function testValidationWithLaravelRulesMessagesAndAttributes(): void
    {
        $this
            ->artisan(DummyPromptsWithLaravelRulesMessagesAndAttributesCommand::class)
            ->expectsQuestion('What is your name?', '')
            ->expectsOutputToContain('Your full name is mandatory.');
    }
}
