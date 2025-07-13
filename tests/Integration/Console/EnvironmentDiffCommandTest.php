<?php

namespace Illuminate\Tests\Integration\Console;

use Orchestra\Testbench\TestCase;

class EnvironmentDiffCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_it_compares_environment_files()
    {
        // Create test environment files in the base path
        $baseContent = "APP_NAME=Laravel\nAPP_ENV=local\nDB_CONNECTION=mysql\n";
        $compareContent = "APP_NAME=MyApp\nAPP_ENV=production\nDB_CONNECTION=mysql\nCUSTOM_VAR=value\n";

        $baseFile = base_path('test_base.env');
        $compareFile = base_path('test_compare.env');

        file_put_contents($baseFile, $baseContent);
        file_put_contents($compareFile, $compareContent);

        $this->artisan('env:diff', [
            'base' => 'test_base.env',
            'compare' => 'test_compare.env',
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

        $baseFile = base_path('test_identical_base.env');
        $compareFile = base_path('test_identical_compare.env');

        file_put_contents($baseFile, $content);
        file_put_contents($compareFile, $content);

        $this->artisan('env:diff', [
            'base' => 'test_identical_base.env',
            'compare' => 'test_identical_compare.env',
        ])
            ->expectsOutputToContain('No differences found')
            ->assertExitCode(0);

        // Clean up
        unlink($baseFile);
        unlink($compareFile);
    }

}