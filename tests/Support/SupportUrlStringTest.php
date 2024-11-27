<?php

namespace Illuminate\Tests\Support;

use Illuminate\Support\UrlString;
use PHPUnit\Framework\TestCase;

class SupportUrlStringTest extends TestCase
{
    public function testUrlStringParsesUrl(): void
    {
        $url = new UrlString('https://taylor:lambo@laravel.com:80/docs?branch=master&theme=dark#eloquent');

        $this->assertSame('https', $url->getScheme());
        $this->assertSame('taylor', $url->getUser());
        $this->assertSame('lambo', $url->getPass());
        $this->assertSame('laravel.com', $url->getHost());
        $this->assertSame(80, $url->getPort());
        $this->assertSame('/docs', $url->getPath());
        $this->assertSame(['branch' => 'master', 'theme' => 'dark'], $url->getQuery());
        $this->assertSame('eloquent', $url->getFragment());
    }

    public function testUrlStringScheme(): void
    {
        $url = new UrlString();

        $this->assertNull($url->getScheme());
        $this->assertNull($url->getProtocol());

        $url->setScheme('https');
        $this->assertSame('https', $url->getScheme());
        $this->assertSame('https://', $url->getProtocol());

        $url->setScheme('file');
        $this->assertSame('file:///', $url->getProtocol());
    }

    public function testUrlStringAuth(): void
    {
        $url = new UrlString();

        $this->assertNull($url->getUser());
        $this->assertNull($url->getPass());

        $url->withAuth('taylor', 'lambo');

        $this->assertSame('taylor', $url->getUser());
        $this->assertSame('lambo', $url->getPass());
    }

    public function testUrlStringHost(): void
    {
        $url = new UrlString();

        $this->assertNull($url->getHost());

        $url->setHost('laravel.com');

        $this->assertSame('laravel.com', $url->getHost());
    }

    public function testUrlStringPort(): void
    {
        $url = new UrlString();

        $this->assertNull($url->getPort());

        $url->setPort(80);

        $this->assertSame(80, $url->getPort());
    }

    public function testUrlStringPath(): void
    {
        $url = new UrlString();

        $this->assertNull($url->getPath());

        $url->setPath('/docs');

        $this->assertSame('/docs', $url->getPath());
    }

    public function testUrlStringQuery(): void
    {
        $url = new UrlString();

        $this->assertEmpty($url->getQuery());
        $this->assertSame('', $url->getQueryString());

        $url->setQuery(['branch' => 'master', 'theme' => 'dark']);
        $this->assertSame(['branch' => 'master', 'theme' => 'dark'], $url->getQuery());

        $url->withQuery(['foo' => 'bar']);
        $this->assertSame(['branch' => 'master', 'theme' => 'dark', 'foo' => 'bar'], $url->getQuery());

        $url->withoutQuery(['branch', 'foo']);
        $this->assertSame(['theme' => 'dark'], $url->getQuery());

        $url->withoutQuery();
        $this->assertEmpty($url->getQuery());
    }

    public function testUrlStringFragment(): void
    {
        $url = new UrlString();

        $this->assertNull($url->getFragment());

        $url->setFragment('eloquent');

        $this->assertSame('eloquent', $url->getFragment());
    }

    public function testToString(): void
    {
        $url = 'https://taylor:lambo@laravel.com:80/docs?branch=master&theme=dark#eloquent';

        $this->assertSame(
            $url,
            (new UrlString($url))->__toString()
        );
    }

    public function testToArray(): void
    {
        $url = 'https://taylor:lambo@laravel.com:80/docs?branch=master&theme=dark#eloquent';

        $this->assertSame(
            [
                'scheme' => 'https',
                'user' => 'taylor',
                'pass' => 'lambo',
                'host' => 'laravel.com',
                'port' => 80,
                'path' => '/docs',
                'query' => ['branch' => 'master', 'theme' => 'dark'],
                'fragment' => 'eloquent',
            ],
            (new UrlString($url))->toArray()
        );
    }
}#
