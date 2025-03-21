<?php

namespace Illuminate\Tests\Integration\Generators;

class BuilderMakeCommandTest extends TestCase
{
    protected $files = [
        'app/Models/Builders/FooBuilder.php',
        'app/Models/Builders/Foo/BarBuilder.php',
    ];

    public function testItCanGenerateBuilderFile()
    {
        $this->artisan('make:builder', ['name' => 'FooBuilder'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace App\Models\Builders;',
            'use Illuminate\Database\Eloquent\Builder;',
            'class FooBuilder extends Builder',
        ], 'app/Models/Builders/FooBuilder.php');

        $this->assertFilenameNotExists('app/Models/Builders/Foo/BarBuilder.php');
    }

    public function testItCanGenerateBuilderFileWithNamespace()
    {
        $this->artisan('make:builder', ['name' => 'Foo\BarBuilder'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace App\Models\Builders\Foo;',
            'use Illuminate\Database\Eloquent\Builder;',
            'class BarBuilder extends Builder',
        ], 'app/Models/Builders/Foo/BarBuilder.php');

        $this->assertFilenameNotExists('app/Models/Builders/FooBuilder.php');
    }
}
