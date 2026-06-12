<?php

namespace Illuminate\Tests\Integration\Generators;

use Illuminate\Console\GeneratorCommand;
use PHPUnit\Framework\Attributes\RequiresOperatingSystem;

#[RequiresOperatingSystem('Linux|Darwin')]
class GeneratorCommandTest extends TestCase
{
    protected array $files = [
        'app/Reverb.php',
        'app/Notifications/InvoicePaid.php',
        'resources/views/notifications/invoice-paid.blade.php',
        'database/migrations/*_create_orbits_table.php',
    ];

    protected function tearDown(): void
    {
        GeneratorCommand::flushState();

        parent::tearDown();
    }

    public function testItAppliesConfiguredPermissionsToCreatedFile()
    {
        GeneratorCommand::createFilesWithPermissions(0666);

        $this->artisan('make:class', ['name' => 'Reverb'])
            ->assertExitCode(0);

        $this->assertFilePermissions(0666, 'app/Reverb.php');
    }

    public function testItAppliesConfiguredPermissionsToAdditionalGeneratedFiles()
    {
        GeneratorCommand::createFilesWithPermissions(0664);

        $this->artisan('make:notification', ['name' => 'InvoicePaid', '--markdown' => 'notifications.invoice-paid'])
            ->assertExitCode(0);

        $this->assertFilePermissions(0664, 'app/Notifications/InvoicePaid.php');
        $this->assertFilePermissions(0664, 'resources/views/notifications/invoice-paid.blade.php');
    }

    public function testItAppliesConfiguredPermissionsToCreatedMigration()
    {
        GeneratorCommand::createFilesWithPermissions(0664);

        $this->artisan('make:migration', ['name' => 'create_orbits_table'])
            ->assertExitCode(0);

        $migration = $this->app['files']->glob($this->app->basePath('database/migrations/*_create_orbits_table.php'))[0] ?? null;

        $this->assertNotNull($migration);
        $this->assertFilePermissions(0664, $migration);
    }

    public function testItUsesDefaultPermissionsWhenNotConfigured()
    {
        $this->artisan('make:class', ['name' => 'Reverb'])
            ->assertExitCode(0);

        $this->assertFilePermissions(0666 & ~umask(), 'app/Reverb.php');
    }

    protected function assertFilePermissions(int $expected, string $path)
    {
        $path = str_starts_with($path, '/') ? $path : $this->app->basePath($path);

        clearstatcache(true, $path);

        $this->assertSame(
            decoct($expected),
            decoct(fileperms($path) & 0777),
            "Failed asserting that [{$path}] has the expected permissions."
        );
    }
}
