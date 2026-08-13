<?php

declare(strict_types=1);

use Illuminate\Auth\AuthManager;

use function PHPStan\Testing\assertType;

$authManager = resolve(AuthManager::class);

$authManager->extend('custom', function (): void {
    assertType('Illuminate\Auth\AuthManager', $this);
});

// Methods declared on the guard contracts keep their contract signatures...
assertType('Illuminate\Contracts\Auth\Authenticatable|null', $authManager->user());
assertType('bool', $authManager->attempt(['email' => 'foo']));

// ... while session specific methods are resolved from the concrete guard.
assertType('Illuminate\Contracts\Auth\Authenticatable|null', $authManager->logoutOtherDevices('password'));
assertType('Illuminate\Auth\SessionGuard', $authManager->setRememberDuration(5));
