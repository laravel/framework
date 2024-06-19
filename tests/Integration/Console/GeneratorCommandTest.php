<?php

namespace Illuminate\Tests\Integration\Console;

use Orchestra\Testbench\Concerns\InteractsWithPublishedFiles;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class GeneratorCommandTest extends TestCase
{
    use InteractsWithPublishedFiles;

    protected $files = [
        'app/Console/Commands/FooCommand.php',
    ];

    public function testItChopsPhpExtension()
    {
        $this->artisan('make:command', ['name' => 'FooCommand.php'])
            ->assertExitCode(0);

        $this->assertFilenameExists('app/Console/Commands/FooCommand.php');
    }

    #[DataProvider('reservedNamesDataProvider')]
    public function testItCannotGenerateClassUsingReservedName($given)
    {
        $this->artisan('make:command', ['name' => $given])
            ->expectsOutputToContain('The name "'.$given.'" is reserved by PHP.')
            ->assertExitCode(0);
    }

    public static function reservedNamesDataProvider()
    {
        yield ['__halt_compiler'];
        yield ['__HALT_COMPILER'];
        yield ['array'];
        yield ['ARRAY'];
        yield ['__class__'];
        yield ['__CLASS__'];
    }
}
