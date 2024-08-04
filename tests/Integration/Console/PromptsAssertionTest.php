<?php

namespace Illuminate\Tests\Integration\Console;

use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Kernel;
use Orchestra\Testbench\TestCase;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\password;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;
use function Laravel\Prompts\textarea;

class PromptsAssertionTest extends TestCase
{
    public function testAssertionForTextPrompt()
    {
        $this->app[Kernel::class]->registerCommand(
            new class extends Command
            {
                protected $signature = 'test:text';

                public function handle()
                {
                    $name = text('What is your name?', 'John');

                    $this->line($name);
                }
            }
        );

        $this
            ->artisan('test:text')
            ->expectsQuestion('What is your name?', 'Jane')
            ->expectsOutput('Jane');
    }

    public function testAssertionForTextareaPrompt()
    {
        $this->app[Kernel::class]->registerCommand(
            new class extends Command
            {
                protected $signature = 'test:textarea';

                public function handle()
                {
                    $name = textarea('What is your name?', 'John');

                    $this->line($name);
                }
            }
        );

        $this
            ->artisan('test:textarea')
            ->expectsQuestion('What is your name?', 'Jane')
            ->expectsOutput('Jane');
    }

    public function testAssertionForPasswordPrompt()
    {
        $this->app[Kernel::class]->registerCommand(
            new class extends Command
            {
                protected $signature = 'test:password';

                public function handle()
                {
                    $name = password('What is your password?');

                    $this->line($name);
                }
            }
        );

        $this
            ->artisan('test:password')
            ->expectsQuestion('What is your password?', 'secret')
            ->expectsOutput('secret');
    }

    public function testAssertionForConfirmPrompt()
    {
        $this->app[Kernel::class]->registerCommand(
            new class extends Command
            {
                protected $signature = 'test:confirm';

                public function handle()
                {
                    $confirmed = confirm('Is your name John?');

                    if ($confirmed) {
                        $this->line('Your name is John.');
                    } else {
                        $this->line('Your name is not John.');
                    }
                }
            }
        );

        $this
            ->artisan('test:confirm')
            ->expectsConfirmation('Is your name John?', 'no')
            ->expectsOutput('Your name is not John.');

        $this
            ->artisan('test:confirm')
            ->expectsConfirmation('Is your name John?', 'yes')
            ->expectsOutput('Your name is John.');
    }

    public function testAssertionForSelectPrompt()
    {
        $this->app[Kernel::class]->registerCommand(
            new class extends Command
            {
                protected $signature = 'test:select';

                public function handle()
                {
                    $name = select(
                        label: 'What is your name?',
                        options: ['John', 'Jane']
                    );

                    $this->line("Your name is $name.");
                }
            }
        );

        $this
            ->artisan('test:select')
            ->expectsChoice('What is your name?', 'Jane', ['John', 'Jane'])
            ->expectsOutput('Your name is Jane.');
    }

    public function testAssertionForRequiredMultiselectPrompt()
    {
        $this->app[Kernel::class]->registerCommand(
            new class extends Command
            {
                protected $signature = 'test:multiselect';

                public function handle()
                {
                    $names = multiselect(
                        label: 'Which names do you like?',
                        options: ['John', 'Jane', 'Sally', 'Jack'],
                        required: true
                    );

                    $this->line(sprintf('You like %s.', implode(', ', $names)));
                }
            }
        );

        $this
            ->artisan('test:multiselect')
            ->expectsChoice('Which names do you like?', ['John', 'Jane'], ['John', 'Jane', 'Sally', 'Jack'])
            ->expectsOutput('You like John, Jane.');
    }

    public function testAssertionForOptionalMultiselectPrompt()
    {
        $this->app[Kernel::class]->registerCommand(
            new class extends Command
            {
                protected $signature = 'test:multiselect';

                public function handle()
                {
                    $names = multiselect(
                        label: 'Which names do you like?',
                        options: ['John', 'Jane', 'Sally', 'Jack'],
                    );

                    if (empty($names)) {
                        $this->line('You like nobody.');
                    } else {
                        $this->line(sprintf('You like %s.', implode(', ', $names)));
                    }
                }
            }
        );

        $this
            ->artisan('test:multiselect')
            ->expectsChoice('Which names do you like?', ['John', 'Jane'], ['John', 'Jane', 'Sally', 'Jack'])
            ->expectsOutput('You like John, Jane.');

        $this
            ->artisan('test:multiselect')
            ->expectsChoice('Which names do you like?', ['None'], ['John', 'Jane', 'Sally', 'Jack'])
            ->expectsOutput('You like nobody.');
    }
}
