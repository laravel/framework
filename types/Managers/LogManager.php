<?php

declare(strict_types=1);

use Illuminate\Log\LogManager;

use function PHPStan\Testing\assertType;

$logManager = resolve(LogManager::class);

$logManager->extend('emergency', function (): void {
    assertType('Illuminate\Log\LogManager', $this);
});
