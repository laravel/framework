<?php

namespace Illuminate\Tests\Console;

use Illuminate\Console\Application;
use Illuminate\Console\OutputStyle;
use Illuminate\Foundation\Console\EnvironmentDiffCommand;
use Illuminate\Foundation\Testing\Concerns\InteractsWithConsole;
use Mockery;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class EnvironmentDiffCommandTest extends TestCase
{
    use InteractsWithConsole;

    protected function tearDown(): void
    {
        parent::tearDown();
        Mockery::close();
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

        $command = new EnvironmentDiffCommand;
        $app = Mockery::mock('Illuminate\Contracts\Foundation\Application');
        $app->shouldReceive('basePath')->andReturn(sys_get_temp_dir());
        $command->setLaravel($app);

        $input = new ArrayInput([
            'base' => basename($baseFile),
            'compare' => basename($compareFile),
        ]);

        $output = new BufferedOutput;
        $command->run($input, $output);

        $result = $output->fetch();

        // Clean up
        unlink($baseFile);
        unlink($compareFile);

        // Assertions
        $this->assertStringContainsString('Comparing', $result);
        $this->assertStringContainsString('Added variables:', $result);
        $this->assertStringContainsString('CUSTOM_VAR=value', $result);
        $this->assertStringContainsString('Changed variables:', $result);
        $this->assertStringContainsString('APP_NAME', $result);
        $this->assertStringContainsString('APP_ENV', $result);
        $this->assertStringContainsString('Summary:', $result);
    }

    public function test_it_handles_missing_files()
    {
        $command = new EnvironmentDiffCommand;
        $app = Mockery::mock('Illuminate\Contracts\Foundation\Application');
        $app->shouldReceive('basePath')->andReturn(sys_get_temp_dir());
        $command->setLaravel($app);

        $input = new ArrayInput([
            'base' => 'nonexistent.env',
            'compare' => 'also-nonexistent.env',
        ]);

        $output = new BufferedOutput;
        $exitCode = $command->run($input, $output);

        $this->assertEquals(1, $exitCode);
        $this->assertStringContainsString('does not exist', $output->fetch());
    }

    public function test_it_shows_no_differences_when_files_are_identical()
    {
        $content = "APP_NAME=Laravel\nAPP_ENV=local\n";

        $baseFile = tempnam(sys_get_temp_dir(), 'env_base');
        $compareFile = tempnam(sys_get_temp_dir(), 'env_compare');

        file_put_contents($baseFile, $content);
        file_put_contents($compareFile, $content);

        $command = new EnvironmentDiffCommand;
        $app = Mockery::mock('Illuminate\Contracts\Foundation\Application');
        $app->shouldReceive('basePath')->andReturn(sys_get_temp_dir());
        $command->setLaravel($app);

        $input = new ArrayInput([
            'base' => basename($baseFile),
            'compare' => basename($compareFile),
        ]);

        $output = new BufferedOutput;
        $command->run($input, $output);

        $result = $output->fetch();

        // Clean up
        unlink($baseFile);
        unlink($compareFile);

        $this->assertStringContainsString('No differences found', $result);
    }
}