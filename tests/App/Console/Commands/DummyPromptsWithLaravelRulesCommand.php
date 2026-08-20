<?php

namespace Illuminate\Tests\App\Console\Commands;

use Illuminate\Console\Command;

use function Laravel\Prompts\text;

class DummyPromptsWithLaravelRulesCommand extends Command
{
    protected $signature = 'prompts-laravel-rules-test';

    public function handle()
    {
        text('What is your name?', validate: 'required');
    }
}
