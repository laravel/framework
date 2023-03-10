<?php

namespace Illuminate\Tests\Foundation\Http\Middleware\Concerns;

use Illuminate\Foundation\Http\Middleware\Concerns\HasSkipCallbacks;
use PHPUnit\Framework\TestCase;

class HasSkipCallbacksTest extends TestCase
{
    public function testHasSkipCallbacks()
    {
        HasSkipCallbacksA::skipWhen(fn ($param) => $param === 'test');
        HasSkipCallbacksA::skipWhen(fn ($param) => $param === 'other');
        $hasSkipCallbacksA = new HasSkipCallbacksA();

        self::assertFalse($hasSkipCallbacksA->shouldSkipDueToCallback('callback'));
        self::assertTrue($hasSkipCallbacksA->shouldSkipDueToCallback('test'));
        self::assertTrue($hasSkipCallbacksA->shouldSkipDueToCallback('other'));
        HasSkipCallbacksA::clearSkips();
        self::assertFalse($hasSkipCallbacksA->shouldSkipDueToCallback('test'));
        self::assertFalse($hasSkipCallbacksA->shouldSkipDueToCallback('other'));
    }

    public function testClassesDontShareCallbacks()
    {
        HasSkipCallbacksA::skipWhen(fn ($param) => $param === 'test');
        HasSkipCallbacksB::skipWhen(fn ($param) => $param === 'callback');
        $hasSkipCallbacksA = new HasSkipCallbacksA();
        $hasSkipCallbacksB = new HasSkipCallbacksB();

        self::assertFalse($hasSkipCallbacksA->shouldSkipDueToCallback('callback'));
        self::assertTrue($hasSkipCallbacksA->shouldSkipDueToCallback('test'));
        self::assertFalse($hasSkipCallbacksB->shouldSkipDueToCallback('test'));
        self::assertTrue($hasSkipCallbacksB->shouldSkipDueToCallback('callback'));
        HasSkipCallbacksA::clearSkips();
        HasSkipCallbacksB::clearSkips();
    }
}

class HasSkipCallbacksA
{
    use HasSkipCallbacks;
}

class HasSkipCallbacksB
{
    use HasSkipCallbacks;
}
