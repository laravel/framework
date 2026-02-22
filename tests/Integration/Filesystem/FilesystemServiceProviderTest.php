<?php

namespace Illuminate\Tests\Integration\Filesystem;

use Illuminate\Filesystem\FilesystemServiceProvider;
use Illuminate\Support\Facades\Route;
use InvalidArgumentException;
use Orchestra\Testbench\TestCase;

class FilesystemServiceProviderTest extends TestCase
{
    public function test_it_throws_when_served_disks_have_conflicting_uris()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The [other] disk conflicts with the [local] disk at [/storage]. Each served disk must have a unique [url].');

        config(['filesystems.disks' => [
            'local' => [
                'driver' => 'local',
                'root' => storage_path('app'),
                'serve' => true,
            ],
            'other' => [
                'driver' => 'local',
                'root' => storage_path('other'),
                'serve' => true,
            ],
        ]]);

        (new FilesystemServiceProvider($this->app))->boot();
    }
}
