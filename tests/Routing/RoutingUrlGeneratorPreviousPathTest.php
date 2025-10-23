<?php

namespace Illuminate\Tests\Routing;

use Illuminate\Http\Request;
use Illuminate\Routing\RouteCollection;
use Illuminate\Routing\UrlGenerator;
use PHPUnit\Framework\TestCase;

class RoutingUrlGeneratorPreviousPathTest extends TestCase
{
    protected function getUrlGenerator($referer = null, $host = 'www.foo.com', $scheme = 'http')
    {
        $routes = new RouteCollection;

        $request = Request::create("{$scheme}://{$host}/");

        if ($referer) {
            $request->headers->set('referer', $referer);
        }

        return new UrlGenerator($routes, $request);
    }

    public function testPreviousPathWithSameDomainUrl()
    {
        $url = $this->getUrlGenerator('http://www.foo.com/bar/baz?query=value');

        $this->assertSame('/bar/baz', $url->previousPath());
    }

    public function testPreviousPathWithSameDomainRootUrl()
    {
        $url = $this->getUrlGenerator('http://www.foo.com/');

        $this->assertSame('/', $url->previousPath());
    }

    public function testPreviousPathWithSameDomainUrlAndQueryString()
    {
        $url = $this->getUrlGenerator('http://www.foo.com/products/123?category=electronics&sort=price');

        $this->assertSame('/products/123', $url->previousPath());
    }

    public function testPreviousPathWithSameDomainUrlAndFragment()
    {
        $url = $this->getUrlGenerator('http://www.foo.com/docs/api#authentication');

        $this->assertSame('/docs/api#authentication', $url->previousPath());
    }

    // backward compatibility tests - non-secure mode (old behavior)
    public function testPreviousPathBackwardCompatibilityWithCrossDomain()
    {
        $url = $this->getUrlGenerator('http://evil.com/malicious');

        $this->assertSame('http://evil.com/malicious', $url->previousPath());
    }

    public function testPreviousPathBackwardCompatibilityWithJavascriptSchemes()
    {
        $url = $this->getUrlGenerator('javascript:alert("xss")');

        $this->assertSame('/javascript:alert("xss")', $url->previousPath());
    }

    public function testPreviousPathBackwardCompatibilityWithDataSchemes()
    {
        $url = $this->getUrlGenerator('data:text/html,<script>alert("xss")</script>');

        $this->assertSame('/data:text/html,<script>alert("xss")</script>', $url->previousPath());
    }

    public function testPreviousPathBackwaCompatibilityWithDifferentSchemes()
    {
        $url = $this->getUrlGenerator('https://www.foo.com/secure/area', 'www.foo.com', 'http');

        $this->assertSame('https://www.foo.com/secure/area', $url->previousPath());
    }

    public function testPreviousPathBackwardCompatibilityWithSubdomains()
    {
        $url = $this->getUrlGenerator('http://sub.foo.com/malicious');

        $this->assertSame('http://sub.foo.com/malicious', $url->previousPath());
    }

    public function testPreviousPathBackwardCompatibilityWithDifferentPorts()
    {
        $url = $this->getUrlGenerator('http://www.foo.com:8080/admin', 'www.foo.com', 'http');

        $this->assertSame(':8080/admin', $url->previousPath());
    }

    public function testPreviousPathBackwardCompatibilityWithExternalUrlAndFallback()
    {
        $url = $this->getUrlGenerator('https://evil.com/malicious');

        $this->assertSame('https://evil.com/malicious', $url->previousPath('/dashboard'));
    }

    // secure mode tests - enhanced behavior with secure flag
    public function testPreviousPathSecureModeWithSameDomainUrl()
    {
        $url = $this->getUrlGenerator('http://www.foo.com/bar/baz?secure=true');

        $this->assertSame('/bar/baz', $url->previousPath(false, true));
    }

    public function testPreviousPathSecureModeWithSameDomainUrlAndFragment()
    {
        $url = $this->getUrlGenerator('http://www.foo.com/docs/api#authentication');

        $this->assertSame('/docs/api', $url->previousPath(false, true));
    }

    public function testPreviousPathSecureModeWithCrossDomain()
    {
        $url = $this->getUrlGenerator('http://evil.com/malicious');

        $this->assertSame('/', $url->previousPath(false, true));
    }

    public function testPreviousPathSecureModeWithJavascriptSchemes()
    {
        $url = $this->getUrlGenerator('javascript:alert("xss")');

        $this->assertSame('/', $url->previousPath(false, true));
    }

    public function testPreviousPathSecureModeWithDataSchemes()
    {
        $url = $this->getUrlGenerator('data:text/html,<script>alert("xss")</script>');

        $this->assertSame('/', $url->previousPath(false, true));
    }

    public function testPreviousPathSecureModeWithFileSchemes()
    {
        $url = $this->getUrlGenerator('file:///etc/passwd');

        $this->assertSame('/', $url->previousPath(false, true));
    }

    public function testPreviousPathSecureModeWithFallback()
    {
        $url = $this->getUrlGenerator('http://evil.com/malicious');

        $this->assertSame('/home', $url->previousPath('/home', true));
    }

    public function testPreviousPathSecureModeBlocksExternalDomain()
    {
        $url = $this->getUrlGenerator('https://evil-site.com/malicious/path');

        $this->assertSame('/', $url->previousPath(false, true));
    }

    public function testPreviousPathSecureModeBlocksExternalDomainWithAnyPaths()
    {
        $url = $this->getUrlGenerator('https://attacker.com/admin/users');

        $this->assertSame('/', $url->previousPath(false, true));
    }

    public function testPreviousPathSecureModeBlocksDifferentScheme()
    {
        $url = $this->getUrlGenerator('https://www.foo.com/secure/area', 'www.foo.com', 'http');

        $this->assertSame('/', $url->previousPath(false, true));
    }

    public function testPreviousPathSecureModeAllowsSameSchemeWithPaths()
    {
        $url = $this->getUrlGenerator('https://www.foo.com/secures/area', 'www.foo.com', 'https');

        $this->assertSame('/secures/area', $url->previousPath(false, true));
    }

    public function testPreviousPathSecureModeBlocksSubdomainAttack()
    {
        $url = $this->getUrlGenerator('http://sub.foo.com/malicious');

        $this->assertSame('/', $url->previousPath(false, true));
    }

    public function testPreviousPathSecureModeBlocksDifferentPortBasedAttack()
    {
        $url = $this->getUrlGenerator('http://www.foo.com:8080/admin', 'www.foo.com', 'http');

        $this->assertSame('/', $url->previousPath(false, true));
    }

    public function testPreviousPathSecureModeAllowsSameDomainWithSamePortUrl()
    {
        $url = $this->getUrlGenerator('http://www.foo.com:8080/allowed', 'www.foo.com:8080', 'http');

        $this->assertSame('/allowed', $url->previousPath(false, true));
    }

    public function testPreviousPathSecureModeBlocksDataUri()
    {
        $url = $this->getUrlGenerator('data:text/html,<script>alert("xss")</script>');

        $this->assertSame('/', $url->previousPath(false, true));
    }

    public function testPreviousPathSecureModeBlocksJavascriptUri()
    {
        $url = $this->getUrlGenerator('javascript:alert("xss")');

        $this->assertSame('/', $url->previousPath(false, true));
    }

    public function testPreviousPathSecureModeAllowsJavascriptUriIfOriginIsSame()
    {
        $url = $this->getUrlGenerator('http://www.foo.com/javascript:alert("same-origin")', 'www.foo.com');

        $this->assertSame('/javascript:alert("same-origin")', $url->previousPath(false, true));
    }

    public function testPreviousPathSecureModeBlocksFileUri()
    {
        $url = $this->getUrlGenerator('file:///etc/passwd');

        $this->assertSame('/', $url->previousPath(false, true));
    }

    public function testPreviousPathHandlesEmptyReferer()
    {
        $url = $this->getUrlGenerator('');

        $this->assertSame('/', $url->previousPath());
    }

    public function testPreviousPathHandlesNullReferer()
    {
        $url = $this->getUrlGenerator(null);

        $this->assertSame('/', $url->previousPath());
    }

    public function testPreviousPathSecureModeWithFallbackForExternalUrl()
    {
        $url = $this->getUrlGenerator('https://evil.com/malicious');

        $this->assertSame('/dashboard', $url->previousPath('/dashboard', true));
    }

    public function testPreviousPathSecureModeAllowsFileUriIfOriginIsSame()
    {
        $url = $this->getUrlGenerator('http://www.foo.com/file:///etc/same');

        $this->assertSame('/file:///etc/same', $url->previousPath(false, true));
    }

    public function testPreviousPathNormalizesTrailingSlashes()
    {
        $url = $this->getUrlGenerator('http://www.foo.com/path/to/resource/');

        $this->assertSame('/path/to/resource', $url->previousPath());
    }

    public function testPreviousPathHandlesDeepNestedPaths()
    {
        $url = $this->getUrlGenerator('http://www.foo.com/level1/level2/level3/level4/list');

        $this->assertSame('/level1/level2/level3/level4/list', $url->previousPath());
    }

    public function testPreviousPathHandlesSpecialCharactersInPath()
    {
        $url = $this->getUrlGenerator('http://www.foo.com/path-with-dashes/under_scores/123');

        $this->assertSame('/path-with-dashes/under_scores/123', $url->previousPath());
    }

    public function testPreviousPathSecureModeHandlesCaseInsensitiveHost()
    {
        $url = $this->getUrlGenerator('http://WWW.FOO.COM/path', 'www.foo.com');

        $this->assertSame('/path', $url->previousPath(false, true));
    }

    public function testPreviousPathSecureModeHandlesCaseVariations()
    {
        $url = $this->getUrlGenerator('http://www.FOO.com/path', 'www.foo.com');

        $this->assertSame('/path', $url->previousPath(false, true));
    }

    public function testPreviousPathHandlesUrlWithoutPath()
    {
        $url = $this->getUrlGenerator('http://www.foo.com');

        $this->assertSame('/', $url->previousPath());
    }

    public function testPreviousPathHandlesComplexQueryString()
    {
        $url = $this->getUrlGenerator('http://www.foo.com/search?q=php&framework=laravel&sort=popularity&page=34');

        $this->assertSame('/search', $url->previousPath());
    }

    public function testPreviousPathHandlesEncodedCharacters()
    {
        $url = $this->getUrlGenerator('http://www.foo.com/path%20with%20spaces%20encoded/resource');

        $this->assertSame('/path%20with%20spaces%20encoded/resource', $url->previousPath());
    }

    public function testIsSameOriginMethod()
    {
        $url = $this->getUrlGenerator();

        $reflection = new \ReflectionClass($url);
        $method = $reflection->getMethod('isSameOrigin');
        $method->setAccessible(true);

        $this->assertTrue($method->invoke(
            $url,
            ['host' => 'www.foo.com', 'scheme' => 'http'],
            ['host' => 'www.foo.com', 'scheme' => 'http']
        ));

        // different host
        $this->assertFalse($method->invoke(
            $url,
            ['host' => 'evil.com', 'scheme' => 'http'],
            ['host' => 'www.foo.com', 'scheme' => 'http']
        ));

        // different scheme
        $this->assertFalse($method->invoke(
            $url,
            ['host' => 'www.foo.com', 'scheme' => 'https'],
            ['host' => 'www.foo.com', 'scheme' => 'http']
        ));

        // missing component
        $this->assertFalse($method->invoke($url, false, ['host' => 'www.foo.com', 'scheme' => 'http']));
        $this->assertFalse($method->invoke($url, ['host' => 'www.foo.com'], ['host' => 'www.foo.com', 'scheme' => 'http']));

        // case insensitive
        $this->assertTrue($method->invoke(
            $url,
            ['host' => 'WWW.FOO.COM', 'scheme' => 'http'],
            ['host' => 'www.foo.com', 'scheme' => 'http']
        ));
    }
}
