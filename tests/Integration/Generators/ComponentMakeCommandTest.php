<?php

namespace Illuminate\Tests\Integration\Generators;

class ComponentMakeCommandTest extends TestCase
{
    protected $files = [
        'app/View/Components/Foo.php',
        'resources/views/components/foo.blade.php',
        'tests/Feature/View/Components/FooTest.php',
        'resources/views/custom/path/foo.blade.php',
        'app/View/Components/Nested/Foo.php',
        'resources/views/components/nested/foo.blade.php',
        'tests/Feature/View/Components/Nested/FooTest.php',
    ];

    public function testItCanGenerateComponentFile()
    {
        $this->artisan('make:component', ['name' => 'Foo'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace App\View\Components;',
            'use Illuminate\View\Component;',
            'class Foo extends Component',
            "return view('components.foo');",
        ], 'app/View/Components/Foo.php');

        $this->assertFilenameExists('resources/views/components/foo.blade.php');
        $this->assertFilenameNotExists('tests/Feature/View/Components/FooTest.php');
    }

    public function testItCanGenerateInlineComponentFile()
    {
        $this->artisan('make:component', ['name' => 'Foo', '--inline' => true])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace App\View\Components;',
            'use Illuminate\View\Component;',
            'class Foo extends Component',
            "return <<<'blade'",
        ], 'app/View/Components/Foo.php');

        $this->assertFilenameNotExists('resources/views/components/foo.blade.php');
    }

    public function testItCanGenerateComponentFileWithTest()
    {
        $this->artisan('make:component', ['name' => 'Foo', '--test' => true])
            ->assertExitCode(0);

        $this->assertFilenameExists('app/View/Components/Foo.php');
        $this->assertFilenameExists('resources/views/components/foo.blade.php');
        $this->assertFilenameExists('tests/Feature/View/Components/FooTest.php');
    }

    public function testItCanGenerateComponentFileWithCustomPath()
    {
        $this->artisan('make:component', ['name' => 'Foo', '--path' => 'custom/path'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace App\View\Components;',
            'use Illuminate\View\Component;',
            'class Foo extends Component',
            "return view('custom.path.foo');",
        ], 'app/View/Components/Foo.php');

        $this->assertFilenameExists('resources/views/custom/path/foo.blade.php');
        $this->assertFilenameNotExists('tests/Feature/View/Components/FooTest.php');
    }

    public function testItCanGenerateNestedComponentFile()
    {
        $this->artisan('make:component', ['name' => 'Nested/Foo'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace App\View\Components\Nested;',
            'use Illuminate\View\Component;',
            'class Foo extends Component',
            "return view('components.nested.foo');",
        ], 'app/View/Components/Nested/Foo.php');

        $this->assertFilenameExists('resources/views/components/nested/foo.blade.php');
        $this->assertFilenameNotExists('tests/Feature/View/Components/Nested/FooTest.php');
    }

    public function testItCanGenerateNestedComponentFileWithCustomPath()
    {
        $this->artisan('make:component', ['name' => 'Nested/Foo', '--path' => 'custom/path'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace App\View\Components\Nested;',
            'use Illuminate\View\Component;',
            'class Foo extends Component',
            "return view('custom.path.foo');",
        ], 'app/View/Components/Nested/Foo.php');

        $this->assertFilenameExists('resources/views/custom/path/foo.blade.php');
        $this->assertFilenameNotExists('tests/Feature/View/Components/Nested/FooTest.php');
    }
}
