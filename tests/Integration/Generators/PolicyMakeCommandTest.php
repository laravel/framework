<?php

namespace Illuminate\Tests\Integration\Generators;

use Illuminate\Support\Facades\File;

class PolicyMakeCommandTest extends TestCase
{
    protected $files = [
        'app/Policies/FooPolicy.php',
        'app/Policies/BarPolicy.php',
        'app/Policies/UserPolicy.php',
        'app/Policies/BazPolicy.php',
        'app/Models/Bar.php',
        'app/Models/User.php',
        'app/Models/Baz.php',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        File::ensureDirectoryExists(app_path('Models'));

        File::put(app_path('Models/Bar.php'), '<?php namespace App\Models; use Illuminate\Database\Eloquent\Model; class Bar extends Model {}');

        File::put(app_path('Models/User.php'), '<?php namespace App\Models; use Illuminate\Foundation\Auth\User as Authenticatable; class User extends Authenticatable {}');

        File::put(app_path('Models/Baz.php'), '<?php namespace App\Models; use Illuminate\Database\Eloquent\Model; class Baz extends Model {}');
    }

    public function testItCanGeneratePolicyFile()
    {
        $this->artisan('make:policy', ['name' => 'FooPolicy'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace App\Policies;',
            'use Illuminate\Foundation\Auth\User;',
            'class FooPolicy',
        ], 'app/Policies/FooPolicy.php');
    }

    public function testItCanGeneratePolicyFileWithModelOption()
    {
        $this->artisan('make:policy', ['name' => 'FooPolicy', '--model' => 'Bar'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace App\Policies;',
            'use App\Models\Bar;',
            'use Illuminate\Foundation\Auth\User;',
            'class FooPolicy',
            'public function viewAny(User $user)',
            'public function view(User $user, Bar $bar)',
            'public function create(User $user)',
            'public function update(User $user, Bar $bar)',
            'public function delete(User $user, Bar $bar)',
            'public function restore(User $user, Bar $bar)',
            'public function forceDelete(User $user, Bar $bar)',
        ], 'app/Policies/FooPolicy.php');
    }
    public function testItCanGeneratePolicyFileWithoutNameWhenModelIsProvided()
    {
        $this->artisan('make:policy', ['--model' => 'Foo'])
            ->expectsQuestion('What should the policy be named?', 'FooPolicy')
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace App\Policies;',
            'use Illuminate\Foundation\Auth\User;',
            'class FooPolicy',
        ], 'app/Policies/FooPolicy.php');
    }

    public function testItCanGeneratePolicyWithShortAllFlag()
    {
        $modelsPath = app_path('Models');
        $policiesPath = app_path('Policies');

        if (! is_dir($modelsPath)) {
            mkdir($modelsPath, 0755, true);
        }

        if (! is_dir($policiesPath)) {
            mkdir($policiesPath, 0755, true);
        }

        $models = [
            'Bar' => '<?php namespace App\Models; use Illuminate\Database\Eloquent\Model; class Bar extends Model {}',
            'User' => '<?php namespace App\Models; use Illuminate\Foundation\Auth\User as Authenticatable; class User extends Authenticatable {}',
            'Baz' => '<?php namespace App\Models; use Illuminate\Database\Eloquent\Model; class Baz extends Model {}',
        ];

        foreach ($models as $name => $content) {
            $modelPath = app_path("Models/{$name}.php");
            file_put_contents($modelPath, $content);
            $this->assertFileExists($modelPath, "Model file {$name}.php was not created");
        }

        $this->artisan('make:policy', ['-a' => true])
            ->assertExitCode(0);

        foreach (array_keys($models) as $name) {
            $policyFile = app_path("Policies/{$name}Policy.php");
            $this->assertFileExists($policyFile, "Policy file {$name}Policy.php was not created");

            if (file_exists($policyFile)) {
                unlink($policyFile);
            }

            $modelFile = app_path("Models/{$name}.php");
            if (file_exists($modelFile)) {
                unlink($modelFile);
            }
        }

        if (is_dir($modelsPath) && count(glob("$modelsPath/*")) === 0) {
            rmdir($modelsPath);
        }
        if (is_dir($policiesPath) && count(glob("$policiesPath/*")) === 0) {
            rmdir($policiesPath);
        }
    }

    public function testItCanGeneratePoliciesForAllModels()
    {
        $models = [
            'Bar' => '<?php namespace App\Models; use Illuminate\Database\Eloquent\Model; class Bar extends Model {}',
            'User' => '<?php namespace App\Models; use Illuminate\Foundation\Auth\User as Authenticatable; class User extends Authenticatable {}',
            'Baz' => '<?php namespace App\Models; use Illuminate\Database\Eloquent\Model; class Baz extends Model {}',
        ];

        foreach ($models as $name => $content) {
            $modelPath = app_path("Models/{$name}.php");
            file_put_contents($modelPath, $content);
            $this->assertFileExists($modelPath, "Model file {$name}.php was not created");
        }

        $this->artisan('make:policy', ['--all' => true])
            ->assertExitCode(0);

        foreach (array_keys($models) as $name) {
            $policyFile = app_path("Policies/{$name}Policy.php");
            $this->assertFileExists($policyFile, "Policy file {$name}Policy.php was not created");

            if (file_exists($policyFile)) {
                unlink($policyFile);
            }

            $modelFile = app_path("Models/{$name}.php");
            if (file_exists($modelFile)) {
                unlink($modelFile);
            }
        }
    }


    /**
     * @return void
     */
    public function testItShowsErrorWhenNoModelsFoundForAllFlag()
    {
        File::deleteDirectory(app_path('Models'));

        $this->artisan('make:policy', ['--all' => true])
            ->expectsOutputToContain('No models found to generate policies for.')
            ->assertExitCode(1);
    }
}
