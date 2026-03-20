<?php

namespace Illuminate\Tests\Integration\Filesystem;

use Illuminate\Filesystem\FilesystemServiceProvider;
use Orchestra\Testbench\TestCase;

class FilesystemServiceProviderTest extends TestCase
{
    public function test_later_disk_takes_precedence_when_served_disks_have_conflicting_uris(): void
    {
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

        // Should not throw - later disk silently takes precedence
        $this->assertCount(2, $this->app->make('config')->get('filesystems.disks'));
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

    public function test_user_defined_disk_overrides_framework_default_at_same_uri(): void
    {
        config(['filesystems.disks' => [
            'local' => [
                'driver' => 'local',
                'root' => storage_path('app/private'),
                'serve' => true,
            ],
            'private' => [
                'driver' => 'local',
                'root' => storage_path('app/private'),
                'serve' => true,
            ],
        ]]);

        (new FilesystemServiceProvider($this->app))->boot();

        $this->assertCount(2, $this->app->make('config')->get('filesystems.disks'));
    }
}
