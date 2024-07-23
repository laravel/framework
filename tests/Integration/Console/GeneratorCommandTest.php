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
        'resources/views/foo/php.blade.php',
        'tests/Feature/fixtures.php/SomeTest.php',
    ];

    public function testItChopsPhpExtension()
    {
        $this->artisan('make:command', ['name' => 'FooCommand.php'])
            ->assertExitCode(0);

        $this->assertFilenameExists('app/Console/Commands/FooCommand.php');

        $this->assertFileContains([
            'class FooCommand extends Command',
        ], 'app/Console/Commands/FooCommand.php');
    }

    public function testItChopsPhpExtensionFromMakeViewCommands()
    {
        $this->artisan('make:view', ['name' => 'foo.php'])
            ->assertExitCode(0);

        $this->assertFilenameExists('resources/views/foo/php.blade.php');
    }

    public function testItOnlyChopsPhpExtensionFromFilename()
    {
        $this->artisan('make:test', ['name' => 'fixtures.php/SomeTest'])
            ->assertExitCode(0);

        $this->assertFilenameExists('tests/Feature/fixtures.php/SomeTest.php');

        $this->assertFileContains([
            'class SomeTest extends TestCase',
        ], 'tests/Feature/fixtures.php/SomeTest.php');
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
