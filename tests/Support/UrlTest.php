<?php

namespace Illuminate\Tests\Support;

use Illuminate\Support\Url;
use Illuminate\Support\UrlQueryParameters;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class UrlTest extends TestCase
{
    public function testParse()
    {
        $url = Url::parse('https://user:pass@example.com:8080/path/to/resource?foo=bar&baz=qux#fragment');

        $this->assertSame('https', $url->scheme);
        $this->assertSame('example.com', $url->host);
        $this->assertSame(8080, $url->port);
        $this->assertSame('user', $url->user);
        $this->assertSame('pass', $url->pass);
        $this->assertSame('/path/to/resource', $url->path);
        $this->assertSame('foo=bar&baz=qux', (string) $url->query);
        $this->assertSame('fragment', $url->fragment);
    }

    public function testParseFailsWithMalformedUrl()
    {
        $url = '///**/foobar/**///';

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Invalid URL [$url].");

        Url::parse($url);
    }

    public function testParseWithMissingComponents()
    {
        $url = Url::parse('/path/to/resource');

        $this->assertNull($url->scheme);
        $this->assertNull($url->host);
        $this->assertNull($url->port);
        $this->assertNull($url->user);
        $this->assertNull($url->pass);
        $this->assertSame('/path/to/resource', $url->path);
        $this->assertInstanceOf(UrlQueryParameters::class, $url->query);
        $this->assertNull($url->fragment);
    }

    public function testQuery()
    {
        $url = Url::parse('https://example.com?foo=bar&baz=qux');

        $this->assertInstanceOf(UrlQueryParameters::class, $url->query);

        $this->assertSame('bar', $url->query->get('foo'));
        $this->assertSame('qux', $url->query->get('baz'));
        $this->assertSame(['foo' => 'bar', 'baz' => 'qux'], $url->query->all());

        $url->query->forget('foo');
        $url->query->set('baz', 'zax');

        $this->assertSame('zax', $url->query->get('baz'));
        $this->assertSame('baz=zax', (string) $url->query);
    }

    public function testToArray()
    {
        $url = Url::parse('https://user:pass@example.com:8080/path/to/resource?foo=bar&baz=qux#fragment');

        $this->assertSame([
            'scheme' => 'https',
            'host' => 'example.com',
            'port' => 8080,
            'user' => 'user',
            'pass' => 'pass',
            'path' => '/path/to/resource',
            'query' => 'foo=bar&baz=qux',
            'fragment' => 'fragment',
        ], $url->toArray());
    }

    public function testToString()
    {
        $rawUrl = 'https://user:pass@example.com:8080/path/to/resource?foo=bar&baz=qux#fragment';

        $url = Url::parse($rawUrl);

        $this->assertSame($rawUrl, (string) $url);
        $this->assertSame('foo=bar&baz=qux', (string) $url->query);
    }
}
