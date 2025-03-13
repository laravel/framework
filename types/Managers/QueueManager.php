<?php
declare(strict_types=1);


use Illuminate\Queue\QueueManager;
use function PHPStan\Testing\assertType;

$queueManager = resolve(QueueManager::class);
$queueManager->extend('custom', function (): void {
    assertType('Illuminate\Queue\QueueManager', $this);
});
