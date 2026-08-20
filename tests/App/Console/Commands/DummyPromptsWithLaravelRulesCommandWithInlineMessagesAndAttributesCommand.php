<?php

namespace Illuminate\Tests\App\Console\Commands;

use Illuminate\Console\Command;

use function Laravel\Prompts\text;

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
