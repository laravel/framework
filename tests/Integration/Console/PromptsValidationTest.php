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
    }

    public function testValidationForPrompts()
    {
        $this
            ->artisan(DummyPromptsValidationCommand::class)
            ->expectsQuestion('What is your name?', '')
            ->expectsOutputToContain('Required!');
    }

    public function testValidationWithLaravelRules()
    {
        $this
            ->artisan(DummyPromptsWithLaravelRulesCommand::class)
            ->expectsQuestion('What is your name?', '')
            ->expectsOutputToContain('The answer field is required.');
    }

    public function testValidationWithLaravelRulesMessagesAndAttributes()
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

class DummyPromptsWithLaravelRulesMessagesAndAttributesCommand extends Command
{
    protected $signature = 'prompts-laravel-rules-messages-attributes-test';

    public function handle()
    {
        text('What is your name?', validate: ['name' => 'required']);
    }

    protected function messages()
    {
        return ['name.required' => 'Your :attribute is mandatory.'];
    }

    protected function attributes()
    {
        return ['name' => 'full name'];
    }
}
