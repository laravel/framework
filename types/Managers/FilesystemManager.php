<?php
declare(strict_types=1);


use Illuminate\Filesystem\FilesystemManager;
use function PHPStan\Testing\assertType;

$filesystemManager = resolve(FilesystemManager::class);
$filesystemManager->extend('local', function (): void {
    assertType('Illuminate\Filesystem\FilesystemManager', $this);
});
