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
        'app/Enums/Profile/HairColor.php',
        'app/Enums/Profile/EyeColor.php',
        'app/Enums/Profile/Admin/Author.php',
    ];

    public function testItCanGenerateEnumFile()
    {
        $this->artisan('make:enum', ['name' => 'StatusEnum'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace App;',
            'enum StatusEnum',
        ], 'app/StatusEnum.php');
    }

    public function testItCanGenerateEnumFileWithString()
    {
        $this->artisan('make:enum', ['name' => 'StringEnum', '--string' => true])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace App;',
            'enum StringEnum: string',
        ], 'app/StringEnum.php');
    }

    public function testItCanGenerateEnumFileWithInt()
    {
        $this->artisan('make:enum', ['name' => 'IntEnum', '--int' => true])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace App;',
            'enum IntEnum: int',
        ], 'app/IntEnum.php');
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

    public function testItDoesNotDoublePrefixWhenEnumsDirExistsAndNameIncludesPrefix()
    {
        /** @var \Illuminate\Filesystem\Filesystem $files */
        $files = $this->app['files'];

        // First call creates app/Enums/Profile/HairColor.php and the app/Enums directory.
        $this->artisan('make:enum', ['name' => 'Enums/Profile/HairColor'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace App\Enums\Profile;',
            'enum HairColor',
        ], 'app/Enums/Profile/HairColor.php');

        // Second call: app/Enums/ now exists. Without the fix, the path would become
        // app/Enums/Enums/Profile/EyeColor.php (double-prefixed).
        $this->artisan('make:enum', ['name' => 'Enums/Profile/EyeColor'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace App\Enums\Profile;',
            'enum EyeColor',
        ], 'app/Enums/Profile/EyeColor.php');

        $files->deleteDirectory(app_path('Enums'));
    }

    public function testItHandlesDeeplyNestedPathWithExistingEnumsDir()
    {
        /** @var \Illuminate\Filesystem\Filesystem $files */
        $files = $this->app['files'];

        // Simulate app/Enums/ already existing (e.g. created by a prior make:enum run).
        $files->ensureDirectoryExists(app_path('Enums'));

        $this->artisan('make:enum', ['name' => 'Enums/Profile/Admin/Author'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace App\Enums\Profile\Admin;',
            'enum Author',
        ], 'app/Enums/Profile/Admin/Author.php');

        $files->deleteDirectory(app_path('Enums'));
    }
}
