<?php

namespace Auth;

use Illuminate\Auth\Middleware\RedirectIfAuthenticated;
use PHPUnit\Framework\TestCase;

class RedirectIfAuthenticatedMiddlewareTest extends TestCase
{
    public function testItCanGenerateDefinitionViaStaticMethod()
    {
        $signature = (string) RedirectIfAuthenticated::using('foo');
        $this->assertSame('Illuminate\Auth\Middleware\RedirectIfAuthenticated:foo', $signature);

        $signature = (string) RedirectIfAuthenticated::using('foo', 'bar');
        $this->assertSame('Illuminate\Auth\Middleware\RedirectIfAuthenticated:foo,bar', $signature);

        $signature = (string) RedirectIfAuthenticated::using('foo', 'bar', 'baz');
        $this->assertSame('Illuminate\Auth\Middleware\RedirectIfAuthenticated:foo,bar,baz', $signature);
    }
}
