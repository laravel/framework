<?php

namespace Illuminate\Tests\Foundation;

use Laravel;
use stdClass;
use Exception;
use Mockery as m;
use Illuminate\Support\Str;
use Illuminate\Foundation\Mix;
use PHPUnit\Framework\TestCase;
use Illuminate\Foundation\Application;

class FoundationHelpersTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testCache()
    {
        $app = new Application;
        $app['cache'] = $cache = m::mock(stdClass::class);

        // 1. Laravel::cache()
        $this->assertInstanceOf(stdClass::class, Laravel::cache());

        // 2. Laravel::cache(['foo' => 'bar'], 1);
        $cache->shouldReceive('put')->once()->with('foo', 'bar', 1);
        Laravel::cache(['foo' => 'bar'], 1);

        // 3. Laravel::cache('foo');
        $cache->shouldReceive('get')->once()->with('foo')->andReturn('bar');
        $this->assertEquals('bar', Laravel::cache('foo'));

        // 4. Laravel::cache('foo', null);
        $cache->shouldReceive('get')->once()->with('foo', null)->andReturn('bar');
        $this->assertEquals('bar', Laravel::cache('foo', null));

        // 5. Laravel::cache('baz', 'default');
        $cache->shouldReceive('get')->once()->with('baz', 'default')->andReturn('default');
        $this->assertEquals('default', Laravel::cache('baz', 'default'));
    }

    public function testCacheThrowsAnExceptionIfAnExpirationIsNotProvided()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('You must specify an expiration time when setting a value in the cache.');

        Laravel::cache(['foo' => 'bar']);
    }

    public function testUnversionedElixir()
    {
        $file = 'unversioned.css';

        Laravel::app()->singleton('path.public', function () {
            return __DIR__;
        });

        touch(Laravel::publicPath($file));

        $this->assertEquals('/'.$file, Laravel::elixir($file));

        unlink(Laravel::publicPath($file));
    }

    public function testMixDoesNotIncludeHost()
    {
        $manifest = $this->makeManifest();

        $result = Laravel::mix('/unversioned.css');

        $this->assertSame('/versioned.css', $result->toHtml());

        unlink($manifest);
    }

    public function testMixCachesManifestForSubsequentCalls()
    {
        $manifest = $this->makeManifest();
        Laravel::mix('unversioned.css');
        unlink($manifest);

        $result = Laravel::mix('/unversioned.css');

        $this->assertSame('/versioned.css', $result->toHtml());
    }

    public function testMixAssetMissingStartingSlashHaveItAdded()
    {
        $manifest = $this->makeManifest();

        $result = Laravel::mix('unversioned.css');

        $this->assertSame('/versioned.css', $result->toHtml());

        unlink($manifest);
    }

    public function testMixMissingManifestThrowsException()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('The Mix manifest does not exist.');

        Laravel::mix('unversioned.css', 'missing');
    }

    public function testMixWithManifestDirectory()
    {
        mkdir($directory = __DIR__.'/mix');
        $manifest = $this->makeManifest('mix');

        $result = Laravel::mix('unversioned.css', 'mix');

        $this->assertSame('/mix/versioned.css', $result->toHtml());

        unlink($manifest);
        rmdir($directory);
    }

    public function testMixManifestDirectoryMissingStartingSlashHasItAdded()
    {
        mkdir($directory = __DIR__.'/mix');
        $manifest = $this->makeManifest('/mix');

        $result = Laravel::mix('unversioned.css', 'mix');

        $this->assertSame('/mix/versioned.css', $result->toHtml());

        unlink($manifest);
        rmdir($directory);
    }

    public function testMixHotModuleReloadingGetsUrlFromFileWithHttps()
    {
        $path = $this->makeHotModuleReloadFile('https://laravel.com/docs');

        $result = Laravel::mix('unversioned.css');

        $this->assertSame('//laravel.com/docs/unversioned.css', $result->toHtml());

        unlink($path);
    }

    public function testMixHotModuleReloadingGetsUrlFromFileWithHttp()
    {
        $path = $this->makeHotModuleReloadFile('http://laravel.com/docs');

        $result = Laravel::mix('unversioned.css');

        $this->assertSame('//laravel.com/docs/unversioned.css', $result->toHtml());

        unlink($path);
    }

    public function testMixHotModuleReloadingGetsUrlFromFileWithManifestDirectoryAndHttps()
    {
        mkdir($directory = __DIR__.'/mix');
        $path = $this->makeHotModuleReloadFile('https://laravel.com/docs', 'mix');

        $result = Laravel::mix('unversioned.css', 'mix');

        $this->assertSame('//laravel.com/docs/unversioned.css', $result->toHtml());

        unlink($path);
        rmdir($directory);
    }

    public function testMixHotModuleReloadingGetsUrlFromFileWithManifestDirectoryAndHttp()
    {
        mkdir($directory = __DIR__.'/mix');
        $path = $this->makeHotModuleReloadFile('http://laravel.com/docs', 'mix');

        $result = Laravel::mix('unversioned.css', 'mix');

        $this->assertSame('//laravel.com/docs/unversioned.css', $result->toHtml());

        unlink($path);
        rmdir($directory);
    }

    public function testMixHotModuleReloadingUsesLocalhostIfNoHttpScheme()
    {
        $path = $this->makeHotModuleReloadFile('');

        $result = Laravel::mix('unversioned.css');

        $this->assertSame('//localhost:8080/unversioned.css', $result->toHtml());

        unlink($path);
    }

    public function testMixHotModuleReloadingWithManifestDirectoryUsesLocalhostIfNoHttpScheme()
    {
        mkdir($directory = __DIR__.'/mix');
        $path = $this->makeHotModuleReloadFile('', 'mix');

        $result = Laravel::mix('unversioned.css', 'mix');

        $this->assertSame('//localhost:8080/unversioned.css', $result->toHtml());

        unlink($path);
        rmdir($directory);
    }

    protected function makeHotModuleReloadFile($url, $directory = '')
    {
        Laravel::app()->singleton('path.public', function () {
            return __DIR__;
        });

        $path = Laravel::publicPath(Str::finish($directory, '/').'hot');

        // Laravel mix when run 'hot' has a new line after the
        // url, so for consistency this "\n" is added.
        file_put_contents($path, "{$url}\n");

        return $path;
    }

    protected function makeManifest($directory = '')
    {
        Laravel::app()->singleton('path.public', function () {
            return __DIR__;
        });

        $path = Laravel::publicPath(Str::finish($directory, '/').'mix-manifest.json');

        touch($path);

        // Laravel mix prints JSON pretty and with escaped
        // slashes, so we are doing that here for consistency.
        $content = json_encode(['/unversioned.css' => '/versioned.css'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        file_put_contents($path, $content);

        return $path;
    }

    public function testMixIsSwappableForTests()
    {
        (new Application)->instance(Mix::class, function () {
            return 'expected';
        });

        $this->assertSame('expected', Laravel::mix('asset.png'));
    }
}
