<?php

namespace Illuminate\Tests\Integration\Console;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\File;
use Mockery as m;
use Orchestra\Testbench\TestCase;

class EnvironmentChangeCommandTest extends TestCase
{
    protected $filesystem;

    protected function setUp(): void
    {
        parent::setUp();

        $this->filesystem = m::spy(Filesystem::class);
        File::swap($this->filesystem);
    }

    public function testItFailsWhenEnvironmentFileDoesNotExist()
    {
        $this->filesystem->shouldReceive('exists')
            ->once()
            ->with(base_path('.env.example'))
            ->andReturn(false);

        $this->artisan('env:change', ['environment' => 'example'])
            ->expectsOutput('The environment file .env.example does not exist.')
            ->assertExitCode(1);
    }

    public function testItChangesEnvironmentFileSuccessfully()
    {
        $this->filesystem->shouldReceive('exists')
            ->once()
            ->with(base_path('.env.example'))
            ->andReturn(true);

        $this->filesystem->shouldReceive('copy')
            ->once()
            ->with(base_path('.env.example'), base_path('.env'))
            ->andReturn(true);

        $this->artisan('env:change', ['environment' => 'example'])
            ->expectsOutput('Environment changed to .env.example')
            ->assertExitCode(0);
    }

    public function testItFailsWhenCopyingFails()
    {
        $this->filesystem->shouldReceive('exists')
            ->once()
            ->with(base_path('.env.example'))
            ->andReturn(true);

        $this->filesystem->shouldReceive('copy')
            ->once()
            ->with(base_path('.env.example'), base_path('.env'))
            ->andReturn(false);

        $this->artisan('env:change', ['environment' => 'example'])
            ->expectsOutput('Failed to change environment to .env.example')
            ->assertExitCode(1);
    }
}