<?php

namespace Illuminate\Tests\Integration\Console;

use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Kernel;
use Orchestra\Testbench\TestCase;

use function Laravel\Prompts\text;

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

class DummyPromptsValidationCommand extends Command
{
    protected $signature = 'prompts-validation-test';

    public function handle()
    {
        text('What is your name?', validate: fn ($value) => $value == '' ? 'Required!' : null);
    }
}

class DummyPromptsWithLaravelRulesCommand extends Command
{
    protected $signature = 'prompts-laravel-rules-test';

    public function handle()
    {
        text('What is your name?', validate: 'required');
    }
}

class DummyPromptsWithLaravelRulesCommandWithInlineMessagesAndAttributesCommand extends Command
{
    protected $signature = 'prompts-laravel-rules-inline-test';

    public function handle()
    {
        text('What is your name?', validate: literal(
            rules: ['name' => 'required'],
            messages: ['name.required' => 'Your :attribute is mandatory.'],
            attributes: ['name' => 'full name'],
        ));
    }
}

class DummyPromptsWithLaravelRulesMessagesAndAttributesCommand extends Command
{
    protected $signature = 'prompts-laravel-rules-messages-attributes-test';

    public function handle()
    {
        text('What is your name?', validate: ['name' => 'required']);
    }

    protected function validationMessages()
    {
        return ['name.required' => 'Your :attribute is mandatory.'];
    }

    protected function validationAttributes()
    {
        return ['name' => 'full name'];
    }
}
