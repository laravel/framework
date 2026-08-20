<?php

namespace Illuminate\Tests\App\Console\Commands;

use Illuminate\Console\Command;

use function Laravel\Prompts\text;

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
