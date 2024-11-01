<?php

namespace Illuminate\Tests\Support;

use Illuminate\Support\Url;
use PHPUnit\Framework\TestCase;
use Illuminate\Support\UrlQueryParameters;

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
        $this->assertSame('foo=bar&baz=qux', $url->query);
        $this->assertSame('fragment', $url->fragment);
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
        $this->assertNull($url->query);
        $this->assertNull($url->fragment);
    }

    public function testQuery()
    {
        $url = Url::parse('https://example.com?foo=bar&baz=qux');

        $this->assertInstanceOf(UrlQueryParameters::class, $url->query());
        $this->assertSame('bar', $url->query()->get('foo'));
        $this->assertSame('qux', $url->query()->get('baz'));
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
}
