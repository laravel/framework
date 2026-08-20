<?php

namespace Illuminate\Tests\App\Console\Commands;

use Illuminate\Console\Command;

use function Laravel\Prompts\text;

class DummyPromptsValidationCommand extends Command
{
    protected $signature = 'prompts-validation-test';

    public function handle()
    {
        text('What is your name?', validate: fn ($value) => $value == '' ? 'Required!' : null);
    }
}
