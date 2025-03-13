<?php
declare(strict_types=1);

use Illuminate\Auth\AuthManager;
use function PHPStan\Testing\assertType;

$authManager = resolve(AuthManager::class);
$authManager->extend('token', function (): void {
    assertType('Illuminate\Auth\AuthManager',$this);
});
