<?php

namespace Integration\Generators;

use Illuminate\Tests\Integration\Generators\TestCase;

class ActionMakeCommandTest extends TestCase
{
    protected $files = [
        'app/Actions/FooAction.php',
    ];

    public function testItCanGenerateActionFile()
    {
        $this->artisan('make:action', ['name' => 'FooAction'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace App\Actions;',
            'class FooAction',
            'use Illuminate\Support\Facades\DB;',
            'public function execute(array $attributes)',
        ], 'app/Actions/FooAction.php');
    }

    public function testItCanGenerateActionFileInActionsFolder()
    {
        $actionsFolderPath = app_path('Actions');

        /** @var \Illuminate\Filesystem\Filesystem $files */
        $files = $this->app['files'];

        $files->ensureDirectoryExists($actionsFolderPath);

        $this->artisan('make:action', ['name' => 'FooAction'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace App\Actions;',
            'class FooAction',
        ], 'app/Actions/FooAction.php');

        $files->deleteDirectory($actionsFolderPath);
    }
}

