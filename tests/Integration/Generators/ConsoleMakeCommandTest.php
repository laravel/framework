<?php

namespace Illuminate\Tests\Integration\Generators;

class ConsoleMakeCommandTest extends TestCase
{
    protected $files = [
        'app/Console/Commands/FooCommand.php',
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
}
