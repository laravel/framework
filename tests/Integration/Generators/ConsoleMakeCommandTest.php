<?php

namespace Illuminate\Tests\Integration\Generators;

class ConsoleMakeCommandTest extends TestCase
{
    protected $files = [
        'app/Console/Commands/FooCommand.php',
    ];

    /** @test */
    public function it_can_generate_command_file()
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

    /** @test */
    public function it_can_generate_command_file_with_command_name()
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
