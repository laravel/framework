<?php

namespace Illuminate\Tests\Integration\Generators;

class ViewMakeCommandTest extends TestCase
{
    protected $files = [
        'resources/views/foo.blade.php',
        'tests/Feature/View/FooTest.php',
    ];

    public function testItCanGenerateViewFile()
    {
        $this->artisan('make:view', ['name' => 'foo'])
            ->assertExitCode(0);

        $this->assertFilenameExists('resources/views/foo.blade.php');
        $this->assertFilenameNotExists('tests/Feature/View/FooTest.php');
    }

    public function testItCanGenerateViewFileWithTest()
    {
        $this->artisan('make:view', ['name' => 'foo', '--test' => true])
            ->assertExitCode(0);

        $this->assertFilenameExists('resources/views/foo.blade.php');
        $this->assertFilenameExists('tests/Feature/View/FooTest.php');
    }
}
