<?php

namespace Integration\Generators;

use Illuminate\Tests\Integration\Generators\TestCase;

class EnumMakeCommandTest extends TestCase
{
    protected $files = [
        'app/IntEnum.php',
        'app/StatusEnum.php',
        'app/StringEnum.php',
        'app/*/OrderStatusEnum.php',
    ];

    public function testItCanGenerateEnumFile()
    {
        $this->artisan('make:enum', ['name' => 'StatusEnum'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace App\Enums;',
            'enum StatusEnum',
        ], 'app/Enums/StatusEnum.php');
    }

    public function testItCanGenerateEnumFileWithString()
    {
        $this->artisan('make:enum', ['name' => 'StringEnum', '--string' => true])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace App\Enums;',
            'enum StringEnum: string',
        ], 'app/Enums/StringEnum.php');
    }

    public function testItCanGenerateEnumFileWithInt()
    {
        $this->artisan('make:enum', ['name' => 'IntEnum', '--int' => true])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace App\Enums;',
            'enum IntEnum: int',
        ], 'app/Enums/IntEnum.php');
    }

    public function testItCanGenerateEnumFileInEnumsFolder()
    {
        $enumsFolderPath = app_path('Enums');

        /** @var \Illuminate\Filesystem\Filesystem $files */
        $files = $this->app['files'];

        $files->ensureDirectoryExists($enumsFolderPath);

        $this->artisan('make:enum', ['name' => 'OrderStatusEnum'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace App\Enums;',
            'enum OrderStatusEnum',
        ], 'app/Enums/OrderStatusEnum.php');

        $files->deleteDirectory($enumsFolderPath);
    }

    public function testItCanGenerateEnumFileInEnumerationsFolder()
    {
        $enumerationsFolderPath = app_path('Enumerations');

        /** @var \Illuminate\Filesystem\Filesystem $files */
        $files = $this->app['files'];

        $files->ensureDirectoryExists($enumerationsFolderPath);

        $this->artisan('make:enum', ['name' => 'OrderStatusEnum'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace App\Enumerations;',
            'enum OrderStatusEnum',
        ], 'app/Enumerations/OrderStatusEnum.php');

        $files->deleteDirectory($enumerationsFolderPath);
    }
}
