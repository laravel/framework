<?php

namespace Illuminate\Tests\Integration\View;

use Illuminate\Foundation\Console\ViewClearCommand;
use Illuminate\Support\Facades\File;
use Orchestra\Testbench\TestCase;

class ClearCommandTest extends TestCase
{
    public function test_clear_view_command_should_remove_parallel_test_directories()
    {
        $globResult = [
            '/views/cache/path/filehash123.php',
            '/views/cache/path/test_33',
        ];
        File::expects('glob')->andReturn($globResult);

        File::expects('isDirectory')->with($globResult[0])->andreturn(false);
        File::expects('isDirectory')->with($globResult[1])->andreturn(true);
        File::expects('delete')->with($globResult[0]);
        File::expects('deleteDirectory')->with($globResult[1]);

        $this->artisan(ViewClearCommand::class);
    }
}
