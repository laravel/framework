<?php

namespace Illuminate\Tests\Integration\Generators;

use Illuminate\Filesystem\Filesystem;

class RepositoryMakeCommandTest extends TestCase
{
    protected mixed $files;

    protected function setUp(): void
    {
        parent::setUp();

        $this->files = app(Filesystem::class);

        if (! is_dir(app_path('Repositories'))) {
            mkdir(app_path('Repositories'), 0755, true);
        }
    }

    protected function tearDown(): void
    {
        $repositoryPath = app_path('Repositories/TestRepository.php');
        $userRepositoryPath = app_path('Repositories/UserRepository.php');
        $interfacePath = app_path('UserInterface.php');

        foreach ([$repositoryPath, $userRepositoryPath, $interfacePath] as $path) {
            if ($this->files->exists($path)) {
                $this->files->delete($path);
            }
        }

        if ($this->files->isDirectory(app_path('Repositories')) &&
            empty($this->files->files(app_path('Repositories')))) {
            $this->files->deleteDirectory(app_path('Repositories'));
        }

        parent::tearDown();
    }

    /**
     * Test creating a basic repository without interface.
     */
    public function test_it_can_create_basic_repository(): void
    {
        $this->artisan('make:repository', ['name' => 'TestRepository'])
            ->assertExitCode(0);

        $path = app_path('Repositories/TestRepository.php');
        $this->assertFileExists($path);

        $this->assertFileContains([
            'namespace App\Repositories;',
            'class TestRepository',
        ], 'app/Repositories/TestRepository.php');
    }

    /**
     * Test force option with short option -f.
     *
     * @return void
     */
    public function test_force_with_short_option()
    {
        $this->artisan('make:repository', ['name' => 'TestRepository'])
            ->assertExitCode(0);

        $this->artisan('make:repository', [
            'name' => 'TestRepository',
            '-f' => true,
        ])->assertExitCode(0);

        $this->assertFileExists(app_path('Repositories/TestRepository.php'));
    }
}
