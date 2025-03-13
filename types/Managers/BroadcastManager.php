<?php
declare(strict_types=1);

use \Illuminate\Broadcasting\BroadcastManager;
use function PHPStan\Testing\assertType;

$broadcastManager = resolve(BroadcastManager::class);
$broadcastManager->extend('reverb', function (): void {
    assertType('Illuminate\Broadcasting\BroadcastManager',$this);
});
