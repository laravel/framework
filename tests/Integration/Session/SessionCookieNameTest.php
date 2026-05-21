<?php

namespace Illuminate\Tests\Integration\Session;

use Illuminate\Support\Str;
use PHPUnit\Framework\TestCase;

class SessionCookieNameTest extends TestCase
{
    /**
     * Resolve the default session cookie name the same way config/session.php does
     * for a given APP_NAME value.
     */
    protected function resolveCookieName(string $appName): string
    {
        return Str::slug(Str::snake($appName), '_').'_session';
    }

    public function testSimpleAppNameProducesSnakeCasedCookie()
    {
        $this->assertSame('my_app_session', $this->resolveCookieName('My App'));
        $this->assertSame('laravel_session', $this->resolveCookieName('laravel'));
    }

    public function testAppNameWithBracketsIsStrippedToSafeCharacters()
    {
        $this->assertSame(
            'l_o_c_a_l_my_awesome_app_session',
            $this->resolveCookieName('[LOCAL] My Awesome App'),
        );
    }

    public function testAppNameWithDotIsStrippedToSafeCharacters()
    {
        $this->assertSame('admindomain_session', $this->resolveCookieName('admin.domain'));
        $this->assertSame('examplecom_session', $this->resolveCookieName('example.com'));
    }

    public function testResolvedCookieNameOnlyContainsRfc6265SafeCharacters()
    {
        $names = [
            '[LOCAL] My Awesome App',
            'admin.domain',
            'My App!',
            'foo/bar',
            'one;two',
        ];

        foreach ($names as $name) {
            $this->assertMatchesRegularExpression(
                '/^[A-Za-z0-9_]+$/',
                $this->resolveCookieName($name),
                "Cookie name for [$name] contained unsafe characters.",
            );
        }
    }
}
