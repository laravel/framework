<?php

namespace Illuminate\Tests\Integration\Console;

use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Kernel;
use Orchestra\Testbench\TestCase;

use function Laravel\Prompts\text;
use function Laravel\Prompts\textarea;

class PromptsAssertionTest extends TestCase
{
    protected function defineEnvironment($app)
    {
        $app[Kernel::class]->registerCommand(new DummyPromptsTextareaAssertionCommand());
        $app[Kernel::class]->registerCommand(new DummyPromptsTextAssertionCommand());
    }

    public function testAssertionForTextPrompt()
    {
        $this
            ->artisan(DummyPromptsTextareaAssertionCommand::class)
            ->expectsQuestion('What is your name?', 'John')
            ->expectsOutput('John');
    }

    public function testAssertionForTextareaPrompt()
    {
        $this
            ->artisan(DummyPromptsTextareaAssertionCommand::class)
            ->expectsQuestion('What is your name?', 'John')
            ->expectsOutput('John');
    }
}

class DummyPromptsTextAssertionCommand extends Command
{
    protected $signature = 'ask:text';

    public function handle()
    {
        $name = text('What is your name?', 'John');

        $this->line($name);
    }
}

class DummyPromptsTextareaAssertionCommand extends Command
{
    protected $signature = 'ask:textarea';

    public function handle()
    {
        $name = textarea('What is your name?', 'John');

        $this->line($name);
    }
}
