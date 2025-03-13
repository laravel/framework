<?php
declare(strict_types=1);


use Illuminate\Mail\MailManager;
use function PHPStan\Testing\assertType;

$mailManager = resolve(MailManager::class);
$mailManager->extend('symfony', function (): void {
    assertType('Illuminate\Mail\MailManager', $this);
});
