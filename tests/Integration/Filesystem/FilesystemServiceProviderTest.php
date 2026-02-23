<?php

namespace Illuminate\Tests\Integration\Filesystem;

use Illuminate\Filesystem\FilesystemServiceProvider;
use InvalidArgumentException;
use Orchestra\Testbench\TestCase;

class FilesystemServiceProviderTest extends TestCase
{
    public function test_it_throws_when_served_disks_have_conflicting_uris(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The [other] disk conflicts with the [local] disk at [/storage]. Each served disk must have a unique URL.');

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

    public function test_served_disks_with_unique_urls_do_not_conflict(): void
    {
        config(['filesystems.disks' => [
            'local' => [
                'driver' => 'local',
                'root' => storage_path('app'),
                'serve' => true,
                'url' => '/storage',
            ],
            'other' => [
                'driver' => 'local',
                'root' => storage_path('other'),
                'serve' => true,
                'url' => '/other',
            ],
        ]]);

        (new FilesystemServiceProvider($this->app))->boot();

        $this->assertCount(2, $this->app->make('config')->get('filesystems.disks'));
    }
}
