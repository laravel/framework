<?php

namespace Illuminate\Tests\Integration\Console;

use Orchestra\Testbench\TestCase;

class MakeModelConnectionOptionTest extends TestCase
{
    public function test_model_is_generated_without_connection_property_when_option_is_not_provided()
    {
        $this->artisan('make:model', ['name' => 'Post'])
            ->assertExitCode(0);

        $path = app_path('Models/Post.php');

        $this->assertFileExists($path);
        $this->assertStringNotContainsString(
            'protected $connection',
            file_get_contents($path)
        );
    }

    public function test_model_is_generated_with_connection_property_when_option_is_provided()
    {
        $this->artisan('make:model', [
            'name' => 'Comment',
            '--connection' => 'pgsql',
        ])->assertExitCode(0);

        $path = app_path('Models/Comment.php');

        $this->assertFileExists($path);
        $this->assertStringContainsString(
            "protected \$connection = 'pgsql';",
            file_get_contents($path)
        );
    }
}
