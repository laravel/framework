<?php

namespace Illuminate\Tests\Filesystem;

use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Foundation\Application;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class FilesystemManagerTest extends TestCase
{
    public function testExceptionThrownOnUnsupportedDriver()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Driver [unsupported-disk] is not supported.');

        $filesystem = new FilesystemManager(tap(new Application, function ($app) {
            $app['config'] = ['filesystems.disks.unsupported-disk' => null];
        }));

        $filesystem->disk('unsupported-disk');
    }
}
