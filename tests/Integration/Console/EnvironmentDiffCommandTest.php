<?php

namespace Illuminate\Tests\Integration\Console;

use Illuminate\Foundation\Console\EnvironmentDiffCommand;
use Illuminate\Support\Facades\File;
use Orchestra\Testbench\TestCase;

class EnvironmentDiffCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_it_compares_environment_files()
    {
        // Create test environment files
        $baseContent = "APP_NAME=Laravel\nAPP_ENV=local\nDB_CONNECTION=mysql\n";
        $compareContent = "APP_NAME=MyApp\nAPP_ENV=production\nDB_CONNECTION=mysql\nCUSTOM_VAR=value\n";

        $baseFile = tempnam(sys_get_temp_dir(), 'env_base');
        $compareFile = tempnam(sys_get_temp_dir(), 'env_compare');

        file_put_contents($baseFile, $baseContent);
        file_put_contents($compareFile, $compareContent);

        $this->artisan('env:diff', [
            'base' => basename($baseFile),
            'compare' => basename($compareFile),
        ])
            ->expectsOutputToContain('Comparing')
            ->expectsOutputToContain('Added variables:')
            ->expectsOutputToContain('CUSTOM_VAR=value')
            ->expectsOutputToContain('Changed variables:')
            ->expectsOutputToContain('APP_NAME')
            ->expectsOutputToContain('APP_ENV')
            ->expectsOutputToContain('Summary:')
            ->assertExitCode(0);

        // Clean up
        unlink($baseFile);
        unlink($compareFile);
    }

    public function test_it_handles_missing_files()
    {
        $this->artisan('env:diff', [
            'base' => 'nonexistent.env',
            'compare' => 'also-nonexistent.env',
        ])
            ->expectsOutputToContain('does not exist')
            ->assertExitCode(1);
    }

    public function test_it_shows_no_differences_when_files_are_identical()
    {
        $content = "APP_NAME=Laravel\nAPP_ENV=local\n";

        $baseFile = tempnam(sys_get_temp_dir(), 'env_base');
        $compareFile = tempnam(sys_get_temp_dir(), 'env_compare');

        file_put_contents($baseFile, $content);
        file_put_contents($compareFile, $content);

        $this->artisan('env:diff', [
            'base' => basename($baseFile),
            'compare' => basename($compareFile),
        ])
            ->expectsOutputToContain('No differences found')
            ->assertExitCode(0);

        // Clean up
        unlink($baseFile);
        unlink($compareFile);
    }

    public function test_it_uses_default_files_when_no_arguments_provided()
    {
        // Create .env.example and .env files
        $exampleContent = "APP_NAME=Laravel\nAPP_ENV=local\n";
        $envContent = "APP_NAME=MyApp\nAPP_ENV=production\n";

        $exampleFile = base_path('.env.example');
        $envFile = base_path('.env');

        file_put_contents($exampleFile, $exampleContent);
        file_put_contents($envFile, $envContent);

        $this->artisan('env:diff')
            ->expectsOutputToContain('Comparing .env.example with .env')
            ->expectsOutputToContain('Changed variables:')
            ->expectsOutputToContain('APP_NAME')
            ->expectsOutputToContain('APP_ENV')
            ->assertExitCode(0);

        // Clean up
        unlink($exampleFile);
        unlink($envFile);
    }
}