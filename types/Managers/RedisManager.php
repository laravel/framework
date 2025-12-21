<?php

declare(strict_types=1);

use Illuminate\Redis\RedisManager;

use function PHPStan\Testing\assertType;

$redisManager = resolve(RedisManager::class);

$redisManager->extend('custom', function (): void {
    assertType('Illuminate\Redis\RedisManager', $this);
});
