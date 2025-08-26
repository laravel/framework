<?php

declare(strict_types=1);

use Illuminate\Concurrency\ConcurrencyManager;

use function PHPStan\Testing\assertType;

$concurrencyManager = resolve(ConcurrencyManager::class);

$concurrencyManager->extend('custom', function (): void {
    assertType('Illuminate\Concurrency\ConcurrencyManager', $this);
});
