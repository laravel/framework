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
            ->expectsQuestion('Test', 'bar')
            ->expectsOutputToContain('error!');
    }

    public function testValidationWithLaravelRules()
    {
        $this
            ->artisan(DummyPromptsWithLaravelRulesCommand::class)
            ->expectsQuestion('Test', '')
            ->expectsOutputToContain('The prompt 1 field is required.');
    }

    public function testValidationWithLaravelRulesMessagesAndAttributes()
    {
        $this
            ->artisan(DummyPromptsWithLaravelRulesMessagesAndAttributesCommand::class)
            ->expectsQuestion('Test', '')
            ->expectsOutputToContain('The field named testing is required.');
    }
}

class DummyPromptsValidationCommand extends Command
{
    protected $signature = 'prompts-validation-test';

    public function handle()
    {
        text('Test', validate: fn ($value) => $value == 'foo' ? '' : 'error!');
    }
}

class DummyPromptsWithLaravelRulesCommand extends Command
{
    protected $signature = 'prompts-laravel-rules-test';

    public function handle()
    {
        text('Test', validate: 'required');
    }
}

class DummyPromptsWithLaravelRulesMessagesAndAttributesCommand extends Command
{
    protected $signature = 'prompts-laravel-rules-messages-attributes-test';

    public function handle()
    {
        text('Test', validate: 'required', as: 'test');
    }

    protected function messages()
    {
        return ['test.required' => 'The field named :attribute is required.'];
    }

    protected function attributes()
    {
        return ['test' => 'testing'];
    }
}
