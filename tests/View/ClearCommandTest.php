<?php

namespace Illuminate\Tests\View;

use Illuminate\Container\Container;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Console\ViewClearCommand;
use Mockery as m;
use Orchestra\Testbench\TestCase;

class ClearCommandTest extends TestCase
{
    private $files;

    protected function setUp(): void
    {
        parent::setUp();

        $this->files = m::mock(Filesystem::class);
        Container::setInstance($this->app);
        $this->app->instance('files', $this->files);
    }

    protected function tearDown(): void
    {
        Container::setInstance(null);
        m::close();
        parent::tearDown();
    }

    public function test_clear_view_command_should_remove_parallel_test_directories()
    {
        $globResult = [
            '/views/cache/path/filehash123.php',
            '/views/cache/path/test_33',
        ];
        $this->files->shouldReceive('glob')->once()->andReturn($globResult);

        $this->files->shouldReceive('isDirectory')->once()->with($globResult[0])->andreturn(false);
        $this->files->shouldReceive('isDirectory')->once()->with($globResult[1])->andreturn(true);
        $this->files->shouldReceive('delete')->once()->with($globResult[0]);
        $this->files->shouldReceive('deleteDirectory')->once()->with($globResult[1]);

        $this->artisan(ViewClearCommand::class);
    }
}
