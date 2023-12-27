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
            ->expectsOutputToContain('The answer field is required.');
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
