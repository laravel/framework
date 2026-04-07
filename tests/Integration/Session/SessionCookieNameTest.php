<?php

namespace Illuminate\Tests\Integration\Session;

use Illuminate\Support\Str;
use PHPUnit\Framework\TestCase;

class SessionCookieNameTest extends TestCase
{
    public function testSessionCookieNameStripsNonAlphanumericCharacters()
    {
        $this->assertSame(
            'local_my_app_session',
            Str::slug('[LOCAL] My App', '_').'_session'
        );
    }

    public function testSessionCookieNameHandlesDots()
    {
        $this->assertSame(
            'admindomain_session',
            Str::slug('admin.domain', '_').'_session'
        );
    }

    public function testSessionCookieNameHandlesSimpleAppName()
    {
        $this->assertSame(
            'laravel_session',
            Str::slug('laravel', '_').'_session'
        );
    }

    public function testSessionCookieNameHandlesMultiWordAppName()
    {
        $this->assertSame(
            'my_awesome_app_session',
            Str::slug('My Awesome App', '_').'_session'
        );
    }
}
