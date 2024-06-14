<?php

namespace Illuminate\Tests\Support;

use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use PHPUnit\Framework\TestCase;

class ServiceProviderTest extends TestCase
{
    public function testItFiresBootingCallbacksSequentially()
    {
        $provider = new StuffServiceProvider(
            new Application()
        );

        $values = [];

        $provider->booting(function () use (&$values) {
            $values[] = 1;
        });

        $provider->booting(function () use (&$values) {
            $values[] = 2;
        });

        $provider->booting(function () use (&$values) {
            $values[] = 3;
        });

        $provider->callBootingCallbacks();

        $this->assertSame([1, 2, 3], $values);
    }

    public function testItFiresBootedCallbacksSequentially()
    {
        $provider = new StuffServiceProvider(
            new Application()
        );

        $values = [];

        $provider->booted(function () use (&$values) {
            $values[] = 1;
        });

        $provider->booted(function () use (&$values) {
            $values[] = 2;
        });

        $provider->booted(function () use (&$values) {
            $values[] = 3;
        });

        $provider->callBootedCallbacks();

        $this->assertSame([1, 2, 3], $values);
    }
}

class StuffServiceProvider extends ServiceProvider
{
    //
}
