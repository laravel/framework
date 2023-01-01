<?php

namespace Illuminate\Tests\Foundation;

use Exception;
use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Mix;
use Illuminate\Support\Str;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use stdClass;

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

        // 1. cache()
        $this->assertInstanceOf(stdClass::class, cache());

        // 2. cache(['foo' => 'bar'], 1);
        $cache->shouldReceive('put')->once()->with('foo', 'bar', 1);
        cache(['foo' => 'bar'], 1);

        // 3. cache('foo');
        $cache->shouldReceive('get')->once()->with('foo')->andReturn('bar');
        $this->assertSame('bar', cache('foo'));

        // 4. cache('foo', null);
        $cache->shouldReceive('get')->once()->with('foo', null)->andReturn('bar');
        $this->assertSame('bar', cache('foo', null));

        // 5. cache('baz', 'default');
        $cache->shouldReceive('get')->once()->with('baz', 'default')->andReturn('default');
        $this->assertSame('default', cache('baz', 'default'));
    }

    public function testMixDoesNotIncludeHost()
    {
        $app = new Application;
        $app['config'] = m::mock(Repository::class);
        $app['config']->shouldReceive('get')->with('app.mix_url');
        $app['config']->shouldReceive('get')->with('app.mix_hot_proxy_url');

        $manifest = $this->makeManifest();

        $result = mix('/unversioned.css');

        $this->assertSame('/versioned.css', $result->toHtml());

        unlink($manifest);
    }

    public function testMixCachesManifestForSubsequentCalls()
    {
        $app = new Application;
        $app['config'] = m::mock(Repository::class);
        $app['config']->shouldReceive('get')->with('app.mix_url');
        $app['config']->shouldReceive('get')->with('app.mix_hot_proxy_url');

        $manifest = $this->makeManifest();
        mix('unversioned.css');
        unlink($manifest);

        $result = mix('/unversioned.css');

        $this->assertSame('/versioned.css', $result->toHtml());
    }

    public function testMixAssetMissingStartingSlashHaveItAdded()
    {
        $app = new Application;
        $app['config'] = m::mock(Repository::class);
        $app['config']->shouldReceive('get')->with('app.mix_url');
        $app['config']->shouldReceive('get')->with('app.mix_hot_proxy_url');

        $manifest = $this->makeManifest();

        $result = mix('unversioned.css');

        $this->assertSame('/versioned.css', $result->toHtml());

        unlink($manifest);
    }

    public function testMixMissingManifestThrowsException()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Mix manifest not found');

        mix('unversioned.css', 'missing');
    }

    public function testMixWithManifestDirectory()
    {
        $app = new Application;
        $app['config'] = m::mock(Repository::class);
        $app['config']->shouldReceive('get')->with('app.mix_url');
        $app['config']->shouldReceive('get')->with('app.mix_hot_proxy_url');

        mkdir($directory = __DIR__.'/mix');
        $manifest = $this->makeManifest('mix');

        $result = mix('unversioned.css', 'mix');

        $this->assertSame('/mix/versioned.css', $result->toHtml());

        unlink($manifest);
        rmdir($directory);
    }

    public function testMixManifestDirectoryMissingStartingSlashHasItAdded()
    {
        mkdir($directory = __DIR__.'/mix');
        $manifest = $this->makeManifest('/mix');

        $result = mix('unversioned.css', 'mix');

        $this->assertSame('/mix/versioned.css', $result->toHtml());

        unlink($manifest);
        rmdir($directory);
    }

    public function testMixHotModuleReloadingGetsUrlFromFileWithHttps()
    {
        $path = $this->makeHotModuleReloadFile('https://laravel.com/docs');

        $result = mix('unversioned.css');

        $this->assertSame('//laravel.com/docs/unversioned.css', $result->toHtml());

        unlink($path);
    }

    public function testMixHotModuleReloadingGetsUrlFromFileWithHttp()
    {
        $path = $this->makeHotModuleReloadFile('http://laravel.com/docs');

        $result = mix('unversioned.css');

        $this->assertSame('//laravel.com/docs/unversioned.css', $result->toHtml());

        unlink($path);
    }

    public function testMixHotModuleReloadingGetsUrlFromFileWithManifestDirectoryAndHttps()
    {
        mkdir($directory = __DIR__.'/mix');
        $path = $this->makeHotModuleReloadFile('https://laravel.com/docs', 'mix');

        $result = mix('unversioned.css', 'mix');

        $this->assertSame('//laravel.com/docs/unversioned.css', $result->toHtml());

        unlink($path);
        rmdir($directory);
    }

    public function testMixHotModuleReloadingGetsUrlFromFileWithManifestDirectoryAndHttp()
    {
        mkdir($directory = __DIR__.'/mix');
        $path = $this->makeHotModuleReloadFile('http://laravel.com/docs', 'mix');

        $result = mix('unversioned.css', 'mix');

        $this->assertSame('//laravel.com/docs/unversioned.css', $result->toHtml());

        unlink($path);
        rmdir($directory);
    }

    public function testMixHotModuleReloadingUsesLocalhostIfNoHttpScheme()
    {
        $path = $this->makeHotModuleReloadFile('');

        $result = mix('unversioned.css');

        $this->assertSame('//localhost:8080/unversioned.css', $result->toHtml());

        unlink($path);
    }

    public function testMixHotModuleReloadingWithManifestDirectoryUsesLocalhostIfNoHttpScheme()
    {
        mkdir($directory = __DIR__.'/mix');
        $path = $this->makeHotModuleReloadFile('', 'mix');

        $result = mix('unversioned.css', 'mix');

        $this->assertSame('//localhost:8080/unversioned.css', $result->toHtml());

        unlink($path);
        rmdir($directory);
    }

    protected function makeHotModuleReloadFile($url, $directory = '')
    {
        app()->singleton('path.public', function () {
            return __DIR__;
        });

        $path = public_path(Str::finish($directory, '/').'hot');

        // Laravel mix when run 'hot' has a new line after the
        // url, so for consistency this "\n" is added.
        file_put_contents($path, "{$url}\n");

        return $path;
    }

    protected function makeManifest($directory = '')
    {
        app()->singleton('path.public', function () {
            return __DIR__;
        });

        $path = public_path(Str::finish($directory, '/').'mix-manifest.json');

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

        $this->assertSame('expected', mix('asset.png'));
    }

    public function testFakeReturnsSameInstance()
    {
        app()->instance('config', new ConfigRepository([]));

        $this->assertSame(fake(), fake());
        $this->assertSame(fake(), fake('en_US'));
        $this->assertSame(fake('en_AU'), fake('en_AU'));
        $this->assertNotSame(fake('en_US'), fake('en_AU'));

        app()->flush();
    }

    public function testFakeUsesLocale()
    {
        mt_srand(12345, MT_RAND_PHP);
        app()->instance('config', new ConfigRepository([]));

        // Should fallback to en_US
        $this->assertSame('Arkansas', fake()->state());
        $this->assertSame('Australian Capital Territory', fake('en_AU')->state());
        $this->assertContains(fake('fr_FR')->region(), ['Provence-Alpes-Côte d\'Azur', 'Guadeloupe']);

        app()->instance('config', new ConfigRepository(['app' => ['faker_locale' => 'en_AU']]));
        mt_srand(4, MT_RAND_PHP);

        // Should fallback to en_US
        $this->assertSame('Australian Capital Territory', fake()->state());
    }
}
