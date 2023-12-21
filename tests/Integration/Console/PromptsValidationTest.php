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
    }

    public function testValidationForPrompts()
    {
        $this
            ->artisan(DummyPromptsValidationCommand::class)
            ->expectsQuestion('Test', 'bar')
            ->expectsOutputToContain('error!');
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
