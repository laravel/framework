<?php

declare(strict_types=1);

use Illuminate\Cache\CacheManager;

use function PHPStan\Testing\assertType;

$cacheManager = resolve(CacheManager::class);

$cacheManager->extend('redis', function (): void {
    assertType('Illuminate\Cache\CacheManager', $this);
});
