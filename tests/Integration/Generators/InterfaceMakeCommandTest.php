<?php

namespace Integration\Generators;

use Illuminate\Tests\Integration\Generators\TestCase;

class InterfaceMakeCommandTest extends TestCase
{
    public function testItCanGenerateInterfaceFile()
    {
        $this->artisan('make:interface', ['name' => 'Gateway'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace App;',
            'interface Gateway',
        ], 'app/Gateway.php');
    }

    public function testItCanGenerateInterfaceFileWhenContractsFolderExists()
    {
        $interfacesFolderPath = app_path('Contracts');

        /** @var \Illuminate\Filesystem\Filesystem $files */
        $files = $this->app['files'];

        $files->ensureDirectoryExists($interfacesFolderPath);

        $this->artisan('make:interface', ['name' => 'Gateway'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace App\Contracts;',
            'interface Gateway',
        ], 'app/Contracts/Gateway.php');

        $files->deleteDirectory($interfacesFolderPath);
    }
}
