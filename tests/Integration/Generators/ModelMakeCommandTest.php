<?php

namespace Illuminate\Tests\Integration\Generators;

class ModelMakeCommandTest extends TestCase
{
    protected $files = [
        'app/Models/Foo.php',
        'app/Models/Foo/Bar.php',
        'app/Http/Controllers/FooController.php',
        'app/Http/Controllers/BarController.php',
        'database/factories/FooFactory.php',
        'database/factories/Foo/BarFactory.php',
        'database/migrations/*_create_foos_table.php',
        'database/seeders/FooSeeder.php',
        'tests/Feature/Models/FooTest.php',
    ];

    public function testItCanGenerateModelFile()
    {
        $this->artisan('make:model', ['name' => 'Foo'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace App\Models;',
            'use Illuminate\Database\Eloquent\Model;',
            'class Foo extends Model',
        ], 'app/Models/Foo.php');

        $this->assertFileDoesNotContains([
            '{{ factoryImport }}',
            'use Illuminate\Database\Eloquent\Factories\HasFactory;',
            '{{ factory }}',
            '/** @use HasFactory<\Database\Factories\FooFactory> */',
            'use HasFactory;',
        ], 'app/Models/Foo.php');

        $this->assertFilenameNotExists('app/Http/Controllers/FooController.php');
        $this->assertFilenameNotExists('database/factories/FooFactory.php');
        $this->assertFilenameNotExists('database/seeders/FooSeeder.php');
        $this->assertFilenameNotExists('tests/Feature/Models/FooTest.php');
    }

    public function testItCanGenerateModelFileWithPivotOption()
    {
        $this->artisan('make:model', ['name' => 'Foo', '--pivot' => true])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace App\Models;',
            'use Illuminate\Database\Eloquent\Relations\Pivot;',
            'class Foo extends Pivot',
        ], 'app/Models/Foo.php');
    }

    public function testItCanGenerateModelFileWithMorphPivotOption()
    {
        $this->artisan('make:model', ['name' => 'Foo', '--morph-pivot' => true])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace App\Models;',
            'use Illuminate\Database\Eloquent\Relations\MorphPivot;',
            'class Foo extends MorphPivot',
        ], 'app/Models/Foo.php');
    }

    public function testItCanGenerateModelFileWithControllerOption()
    {
        $this->artisan('make:model', ['name' => 'Foo', '--controller' => true])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace App\Models;',
            'use Illuminate\Database\Eloquent\Model;',
            'class Foo extends Model',
        ], 'app/Models/Foo.php');

        $this->assertFileContains([
            'namespace App\Http\Controllers;',
            'use Illuminate\Http\Request;',
            'class FooController',
        ], 'app/Http/Controllers/FooController.php');

        $this->assertFileNotContains([
            'use App\Models\Foo;',
            'public function index()',
            'public function create()',
            'public function store(Request $request)',
            'public function show(Foo $foo)',
            'public function edit(Foo $foo)',
            'public function update(Request $request, Foo $foo)',
            'public function destroy(Foo $foo)',
        ], 'app/Http/Controllers/FooController.php');

        $this->assertFilenameNotExists('database/factories/FooFactory.php');
        $this->assertFilenameNotExists('database/seeders/FooSeeder.php');
    }

    public function testItCanGenerateModelFileWithFactoryOption()
    {
        $this->artisan('make:model', ['name' => 'Foo', '--factory' => true])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace App\Models;',
            'use Illuminate\Database\Eloquent\Factories\HasFactory;',
            'use Illuminate\Database\Eloquent\Model;',
            'class Foo extends Model',
            '/** @use HasFactory<\Database\Factories\FooFactory> */',
            'use HasFactory;',
        ], 'app/Models/Foo.php');

        $this->assertFileNotContains([
            '{{ factoryImport }}',
            '{{ factory }}',
        ], 'app/Models/Foo.php');

        $this->assertFilenameNotExists('app/Http/Controllers/FooController.php');
        $this->assertFilenameExists('database/factories/FooFactory.php');
        $this->assertFilenameNotExists('database/seeders/FooSeeder.php');
    }

    public function testItCanGenerateModelFileWithFactoryOptionForDeepFolder()
    {
        $this->artisan('make:model', ['name' => 'Foo/Bar', '--factory' => true])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace App\Models\Foo;',
            'use Illuminate\Database\Eloquent\Factories\HasFactory;',
            'use Illuminate\Database\Eloquent\Model;',
            'class Bar extends Model',
            '/** @use HasFactory<\Database\Factories\Foo\BarFactory> */',
            'use HasFactory;',
        ], 'app/Models/Foo/Bar.php');

        $this->assertFileNotContains([
            '{{ factoryImport }}',
            '{{ factory }}',
        ], 'app/Models/Foo/Bar.php');

        $this->assertFilenameNotExists('app/Http/Controllers/Foo/BarController.php');
        $this->assertFilenameExists('database/factories/Foo/BarFactory.php');
        $this->assertFilenameNotExists('database/seeders/Foo/BarSeeder.php');
    }

    public function testItGeneratesModelWithHasFactoryTraitWhenUsingAllOption()
    {
        $this->artisan('make:model', ['name' => 'Foo', '--all' => true])
            ->expectsQuestion('A App\Models\Foo model does not exist. Do you want to generate it?', false)
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace App\Models;',
            'use Illuminate\Database\Eloquent\Factories\HasFactory;',
            'use Illuminate\Database\Eloquent\Model;',
            'class Foo extends Model',
            '/** @use HasFactory<\Database\Factories\FooFactory> */',
            'use HasFactory;',
        ], 'app/Models/Foo.php');

        $this->assertFileNotContains([
            '{{ factoryImport }}',
            '{{ factory }}',
        ], 'app/Models/Foo.php');

        $this->assertFilenameExists('app/Http/Controllers/FooController.php');
        $this->assertFilenameExists('database/factories/FooFactory.php');
        $this->assertFilenameExists('database/seeders/FooSeeder.php');
        $this->assertMigrationFileExists('create_foos_table.php');
    }

    public function testItCanGenerateModelFileWithMigrationOption()
    {
        $this->artisan('make:model', ['name' => 'Foo', '--migration' => true])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace App\Models;',
            'use Illuminate\Database\Eloquent\Model;',
            'class Foo extends Model',
        ], 'app/Models/Foo.php');

        $this->assertMigrationFileContains([
            'use Illuminate\Database\Migrations\Migration;',
            'return new class extends Migration',
            'Schema::create(\'foos\', function (Blueprint $table) {',
            'Schema::dropIfExists(\'foos\');',
        ], 'create_foos_table.php');

        $this->assertFilenameNotExists('app/Http/Controllers/FooController.php');
        $this->assertFilenameNotExists('database/factories/FooFactory.php');
        $this->assertFilenameNotExists('database/seeders/FooSeeder.php');
    }

    public function testItCanGenerateModelFileWithSeederption()
    {
        $this->artisan('make:model', ['name' => 'Foo', '--seed' => true])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace App\Models;',
            'use Illuminate\Database\Eloquent\Model;',
            'class Foo extends Model',
        ], 'app/Models/Foo.php');

        $this->assertFilenameNotExists('app/Http/Controllers/FooController.php');
        $this->assertFilenameNotExists('database/factories/FooFactory.php');
        $this->assertFilenameExists('database/seeders/FooSeeder.php');
    }

    public function testItCanGenerateNestedModelFileWithControllerOption()
    {
        $this->artisan('make:model', ['name' => 'Foo/Bar', '--controller' => true])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace App\Models\Foo;',
            'use Illuminate\Database\Eloquent\Model;',
            'class Bar extends Model',
        ], 'app/Models/Foo/Bar.php');

        $this->assertFileContains([
            'namespace App\Http\Controllers;',
            'use Illuminate\Http\Request;',
            'class BarController',
        ], 'app/Http/Controllers/BarController.php');

        $this->assertFilenameNotExists('database/factories/FooFactory.php');
        $this->assertFilenameNotExists('database/seeders/FooSeeder.php');
    }

    public function testItCanGenerateModelFileWithTest()
    {
        $this->artisan('make:model', ['name' => 'Foo', '--test' => true])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace App\Models;',
            'use Illuminate\Database\Eloquent\Model;',
            'class Foo extends Model',
        ], 'app/Models/Foo.php');

        $this->assertFilenameNotExists('app/Http/Controllers/FooController.php');
        $this->assertFilenameNotExists('database/factories/FooFactory.php');
        $this->assertFilenameNotExists('database/seeders/FooSeeder.php');
        $this->assertFilenameExists('tests/Feature/Models/FooTest.php');
    }

    public function testItAsksForAdditionalComponentsForExistingModel()
    {
        $this->artisan('make:model', ['name' => 'Foo'])
            ->assertExitCode(0);

        $this->artisan('make:model', ['name' => 'Foo'])
            ->assertExitCode(0)
            ->expectsConfirmation('Do you want to generate additional components for the model?', 'yes')
            ->expectsQuestion('Would you like any of the following?', []);
    }
}
