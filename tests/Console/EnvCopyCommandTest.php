<?php

namespace Illuminate\Tests\Console;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\File;
use Illuminate\Tests\Integration\Generators\TestCase;
use Mockery as m;

class EnvCopyCommandTest extends TestCase
{
    protected $tempPath;
    protected $filesMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tempPath = sys_get_temp_dir().'/'.uniqid('laravel_env_test_');
        File::makeDirectory($this->tempPath);

        $this->filesMock = m::mock(Filesystem::class);
        $this->app->instance(Filesystem::class, $this->filesMock);
    }

    protected function getEnvironmentSetUp($app)
    {
        $app->instance('path.base', $this->tempPath);
    }

    protected function tearDown(): void
    {
        m::close();
        File::deleteDirectory($this->tempPath);
        parent::tearDown();
    }

    public function test_it_copies_env_example_to_env_successfully()
    {
        $this->filesMock->shouldReceive('exists')
                        ->with(base_path('.env.example'))
                        ->andReturn(true);
        $this->filesMock->shouldReceive('exists')
                        ->with(base_path('.env'))
                        ->andReturn(false);
        $this->filesMock->shouldReceive('copy')
                        ->with(base_path('.env.example'), base_path('.env'))
                        ->andReturn(true);

        $this->artisan('env:copy')
             ->expectsOutput('Environment file copied successfully from .env.example to .env.')
             ->assertExitCode(0);
    }

    public function test_it_overwrites_env_with_force_option()
    {
        $this->filesMock->shouldReceive('exists')
                        ->with(base_path('.env.example'))
                        ->andReturn(true);
        $this->filesMock->shouldReceive('exists')
                        ->with(base_path('.env'))
                        ->andReturn(true);
        $this->filesMock->shouldReceive('copy')
                        ->with(base_path('.env.example'), base_path('.env'))
                        ->andReturn(true);

        $this->artisan('env:copy --force')
             ->expectsOutput('Environment file copied successfully from .env.example to .env.')
             ->assertExitCode(0);
    }

    public function test_it_copies_custom_env_file_with_env_option()
    {
        $this->filesMock->shouldReceive('exists')
                        ->with(base_path('.env.staging'))
                        ->andReturn(true);
        $this->filesMock->shouldReceive('exists')
                        ->with(base_path('.env'))
                        ->andReturn(false);
        $this->filesMock->shouldReceive('copy')
                        ->with(base_path('.env.staging'), base_path('.env'))
                        ->andReturn(true);

        $this->artisan('env:copy --env=.env.staging')
             ->expectsOutput('Environment file copied successfully from .env.staging to .env.')
             ->assertExitCode(0);
    }

    public function test_it_fails_if_env_exists_and_force_is_not_used()
    {
        $this->filesMock->shouldReceive('exists')
                        ->with(base_path('.env.example'))
                        ->andReturn(true);
        $this->filesMock->shouldReceive('exists')
                        ->with(base_path('.env'))
                        ->andReturn(true);

        $this->artisan('env:copy')
             ->expectsOutput('The .env file already exists. Use --force to overwrite it.')
             ->assertExitCode(1);
    }

    public function test_it_fails_with_invalid_source_path()
    {
        $this->filesMock->shouldNotReceive('exists'); // Should not call exists for invalid path
        $this->filesMock->shouldNotReceive('copy');

        $this->artisan('env:copy --env=invalid_env_file.txt')
             ->expectsOutput('Invalid source file path. Please provide a valid file name starting with "." (e.g., .env.example).')
             ->assertExitCode(1);
    }

    public function test_it_fails_with_empty_source_path()
    {
        $this->filesMock->shouldNotReceive('exists');
        $this->filesMock->shouldNotReceive('copy');

        $this->artisan('env:copy --env=""')
             ->expectsOutput('Invalid source file path. Please provide a valid file name starting with "." (e.g., .env.example).')
             ->assertExitCode(1);
    }

    public function test_it_fails_if_source_file_does_not_exist()
    {
        $this->filesMock->shouldReceive('exists')
                        ->with(base_path('.env.nonexistent'))
                        ->andReturn(false);
        $this->filesMock->shouldNotReceive('copy');

        $this->artisan('env:copy --env=.env.nonexistent')
             ->expectsOutput('The source file .env.nonexistent does not exist in the project root.')
             ->assertExitCode(1);
    }

    public function test_it_fails_on_copy_exception()
    {
        $this->filesMock->shouldReceive('exists')
                        ->with(base_path('.env.example'))
                        ->andReturn(true);
        $this->filesMock->shouldReceive('exists')
                        ->with(base_path('.env'))
                        ->andReturn(false);
        $this->filesMock->shouldReceive('copy')
                        ->with(base_path('.env.example'), base_path('.env'))
                        ->andThrow(new \Exception('Copy failed for some reason.'));

        $this->artisan('env:copy')
             ->expectsOutput('Failed to copy environment file: Copy failed for some reason.')
             ->assertExitCode(1);
    }
}
