<?php

namespace Illuminate\Tests\Integration\Generators;

class ConsoleMakeCommandTest extends TestCase
{
    protected $files = [
        'app/Console/Commands/FooCommand.php',
        'app/Console/Commands/InteractiveCommand.php',
        'app/Console/Commands/SendReportCommand.php',
    ];

    public function testItCanGenerateConsoleFile()
    {
        $this->artisan('make:command', ['name' => 'FooCommand'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace App\Console\Commands;',
            'use Illuminate\Console\Command;',
            'class FooCommand extends Command',
            'protected $signature = \'app:foo-command\';',
        ], 'app/Console/Commands/FooCommand.php');
    }

    public function testItCanGenerateConsoleFileWithCommandOption()
    {
        $this->artisan('make:command', ['name' => 'FooCommand', '--command' => 'foo:bar'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace App\Console\Commands;',
            'use Illuminate\Console\Command;',
            'class FooCommand extends Command',
            'protected $signature = \'foo:bar\';',
        ], 'app/Console/Commands/FooCommand.php');
    }

    public function testInteractiveOptionExists()
    {
        $this->artisan('make:command', ['--help'])
            ->expectsOutputToContain('--interactive')
            ->assertExitCode(0);
    }

    public function testInteractiveModeGeneratesCommandWithArguments()
    {
        $this->artisan('make:command', ['name' => 'SendReportCommand', '--interactive' => true])
            ->expectsQuestion('What is the command signature?', 'report:send')
            ->expectsQuestion('What is the command description?', 'Send daily reports to administrators')
            ->expectsConfirmation('Would you like to add an argument?', 'yes')
            ->expectsQuestion('Argument name?', 'recipient')
            ->expectsQuestion('Argument description?', 'The recipient email address')
            ->expectsChoice('Is this argument required or optional?', 'Required', ['Required', 'Optional', 'Optional array (multiple values)'])
            ->expectsConfirmation('Would you like to add an argument?', 'no')
            ->expectsConfirmation('Would you like to add an option?', 'no')
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace App\Console\Commands;',
            'class SendReportCommand extends Command',
            'protected $signature = \'report:send {recipient : The recipient email address}\';',
            'protected $description = \'Send daily reports to administrators\';',
        ], 'app/Console/Commands/SendReportCommand.php');
    }

    public function testInteractiveModeGeneratesCommandWithOptions()
    {
        $this->artisan('make:command', ['name' => 'InteractiveCommand', '--interactive' => true])
            ->expectsQuestion('What is the command signature?', 'app:interactive')
            ->expectsQuestion('What is the command description?', 'Interactive test command')
            ->expectsConfirmation('Would you like to add an argument?', 'no')
            ->expectsConfirmation('Would you like to add an option?', 'yes')
            ->expectsQuestion('Option name?', 'queue')
            ->expectsQuestion('Option shortcut? (Optional, single letter)', 'Q')
            ->expectsQuestion('Option description?', 'Queue the command execution')
            ->expectsChoice('What type of option is this?', 'Flag (no value)', ['Flag (no value)', 'Optional value', 'Required value', 'Array (multiple values)'])
            ->expectsConfirmation('Would you like to add an option?', 'no')
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace App\Console\Commands;',
            'class InteractiveCommand extends Command',
            'protected $signature = \'app:interactive {-Q|--queue : Queue the command execution}\';',
            'protected $description = \'Interactive test command\';',
        ], 'app/Console/Commands/InteractiveCommand.php');
    }
}
