<?php

namespace Illuminate\Tests\Integration\View;

use Illuminate\Container\Container;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Console\ViewClearCommand;
use Mockery;
use Orchestra\Testbench\TestCase;

class ClearCommandTest extends TestCase
{
    private $files;

    protected function setUp(): void
    {
        parent::setUp();

        $this->files = Mockery::mock(Filesystem::class);
        Container::setInstance($this->app);
        $this->app->instance('files', $this->files);
    }

    protected function tearDown(): void
    {
        Container::setInstance(null);

        parent::tearDown();
    }

    public function test_clear_view_command_should_remove_parallel_test_directories()
    {
        $globResult = [
            '/views/cache/path/filehash123.php',
            '/views/cache/path/test_33',
        ];
        $this->files->expects('glob')->andReturn($globResult);

        $this->files->expects('isDirectory')->with($globResult[0])->andreturn(false);
        $this->files->expects('isDirectory')->with($globResult[1])->andreturn(true);
        $this->files->expects('delete')->with($globResult[0]);
        $this->files->expects('deleteDirectory')->with($globResult[1]);

        $this->artisan(ViewClearCommand::class);
    }
}
