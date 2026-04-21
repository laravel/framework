<?php

namespace Illuminate\Tests\Integration\Generators;

class ViewMakeCommandTest extends TestCase
{
    protected $files = [
        'resources/views/foo.blade.php',
        'resources/views/bar.blade.php',
        'tests/Feature/View/FooTest.php',
        'tests/Feature/View/BarTest.php',
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

    public function testItCanGenerateViewFileWithLayout()
    {
        $this->artisan('make:view', ['name' => 'foo', '--layout' => 'layouts.app'])
            ->assertExitCode(0);

        $this->assertFilenameExists('resources/views/foo.blade.php');

        $content = file_get_contents($this->app->basePath('resources/views/foo.blade.php'));
        $this->assertStringContainsString('@extends(\'layouts.app\')', $content);
    }

    public function testItCanGenerateViewFileWithLayoutAndTest()
    {
        $this->artisan('make:view', ['name' => 'bar', '--layout' => 'layouts.admin', '--test' => true])
            ->assertExitCode(0);

        $this->assertFilenameExists('resources/views/bar.blade.php');
        $this->assertFilenameExists('tests/Feature/View/BarTest.php');

        $content = file_get_contents($this->app->basePath('resources/views/bar.blade.php'));
        $this->assertStringContainsString('@extends(\'layouts.admin\')', $content);
    }
}
