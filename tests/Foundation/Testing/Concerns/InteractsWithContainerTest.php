<?php

namespace Illuminate\Tests\Foundation\Testing\Concerns;

use Illuminate\Foundation\Mix;
use Illuminate\Foundation\Vite;
use Illuminate\Support\Defer\DeferredCallbackCollection;
use Orchestra\Testbench\TestCase;
use stdClass;

class InteractsWithContainerTest extends TestCase
{
    public function testWithoutViteBindsEmptyHandlerAndReturnsInstance()
    {
        $instance = $this->withoutVite();

        $this->assertSame('', app(Vite::class)(['resources/js/app.js'])->toHtml());
        $this->assertSame($this, $instance);
    }

    public function testWithoutViteHandlesReactRefresh()
    {
        $instance = $this->withoutVite();

        $this->assertSame('', app(Vite::class)->reactRefresh());
        $this->assertSame($this, $instance);
    }

    public function testWithoutViteHandlesAsset()
    {
        $instance = $this->withoutVite();

        $this->assertSame('', app(Vite::class)->asset('path/to/asset.png'));
        $this->assertSame($this, $instance);
    }

    public function testWithViteRestoresOriginalHandlerAndReturnsInstance()
    {
        $handler = new stdClass;
        $this->app->instance(Vite::class, $handler);

        $this->withoutVite();
        $instance = $this->withVite();

        $this->assertSame($handler, resolve(Vite::class));
        $this->assertSame($this, $instance);
    }

    public function testWithoutViteReturnsEmptyArrayForPreloadedAssets(): void
    {
        $instance = $this->withoutVite();

        $this->assertSame([], app(Vite::class)->preloadedAssets());
        $this->assertSame($this, $instance);
    }

    public function testWithoutViteWithCallbackExecutesCallbackOnInvoke()
    {
        $assets = [];

        $this->withoutVite(function ($asset, $buildDirectory) use (&$assets) {
            $assets[] = [$asset, $buildDirectory];
        });

        app(Vite::class)(['resources/js/app.js', 'resources/css/app.css'], 'build');

        $this->assertEquals([
            ['resources/js/app.js', 'build'],
            ['resources/css/app.css', 'build'],
        ], $assets);
    }

    public function testWithoutViteWithCallbackExecutesCallbackOnAssetMethod()
    {
        $assets = [];

        $this->withoutVite(function ($asset, $buildDirectory) use (&$assets) {
            $assets[] = [$asset, $buildDirectory];
        });

        app(Vite::class)->asset('resources/js/app.js', 'custom-build');

        $this->assertEquals([
            ['resources/js/app.js', 'custom-build'],
        ], $assets);
    }

    public function testWithoutViteWithCallbackExecutesCallbackOnContentMethod()
    {
        $assets = [];

        $this->withoutVite(function ($asset, $buildDirectory) use (&$assets) {
            $assets[] = [$asset, $buildDirectory];
        });

        app(Vite::class)->content('resources/js/app.js', 'build');

        $this->assertEquals([
            ['resources/js/app.js', 'build'],
        ], $assets);
    }

    public function testWithoutViteStrictThrowsForMissingAsset()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Vite asset does not exist: non-existent-file.js');

        $this->withoutViteStrict();

        app(Vite::class)(['non-existent-file.js']);
    }

    public function testWithoutViteStrictValidatesExistingAssets()
    {
        // Create a temporary asset file for testing
        $assetPath = resource_path('temp-test-asset.js');
        file_put_contents($assetPath, '// test asset');

        try {
            $this->withoutViteStrict();

            // Should not throw for existing file
            $result = app(Vite::class)(['temp-test-asset.js']);
            $this->assertSame('', $result->toHtml());
        } finally {
            if (file_exists($assetPath)) {
                unlink($assetPath);
            }
        }
    }

    public function testWithoutMixBindsEmptyHandlerAndReturnsInstance()
    {
        $instance = $this->withoutMix();

        $this->assertSame('', (string) mix('path/to/asset.png'));
        $this->assertSame($this, $instance);
    }

    public function testWithMixRestoresOriginalHandlerAndReturnsInstance()
    {
        $handler = new stdClass;
        $this->app->instance(Mix::class, $handler);

        $this->withoutMix();
        $instance = $this->withMix();

        $this->assertSame($handler, resolve(Mix::class));
        $this->assertSame($this, $instance);
    }

    public function testWithoutMixWithCallbackExecutesCallbackOnMixCall()
    {
        $assets = [];

        $this->withoutMix(function ($asset, $buildDirectory) use (&$assets) {
            $assets[] = [$asset, $buildDirectory];
        });

        mix('js/app.js');

        $this->assertEquals([
            ['js/app.js', null],
        ], $assets);
    }

    public function testWithoutMixStrictThrowsForMissingAsset()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Mix asset does not exist: js/non-existent-file.js');

        $this->withoutMixStrict();

        mix('js/non-existent-file.js');
    }

    public function testWithoutMixStrictValidatesExistingAssets()
    {
        // Create a temporary asset file for testing
        $assetPath = public_path('temp-test-asset.js');
        file_put_contents($assetPath, '// test asset');

        try {
            $this->withoutMixStrict();

            // Should not throw for existing file
            $result = mix('temp-test-asset.js');
            $this->assertSame('', (string) $result);
        } finally {
            if (file_exists($assetPath)) {
                unlink($assetPath);
            }
        }
    }

    public function testWithoutDefer()
    {
        $called = [];
        defer(function () use (&$called) {
            $called[] = 1;
        });
        $this->assertSame([], $called);

        $instance = $this->withoutDefer();
        defer(function () use (&$called) {
            $called[] = 2;
        });
        $this->assertSame([2], $called);
        $this->assertSame($this, $instance);

        $this->withDefer();
        $this->assertSame([2], $called);
        $this->app[DeferredCallbackCollection::class]->invoke();
        $this->assertSame([2, 1], $called);
    }

    public function testForgetMock()
    {
        $this->mock(InstanceStub::class)
            ->shouldReceive('execute')
            ->once()
            ->andReturn('bar');

        $this->assertSame('bar', $this->app->make(InstanceStub::class)->execute());

        $this->forgetMock(InstanceStub::class);
        $this->assertSame('foo', $this->app->make(InstanceStub::class)->execute());
    }
}

class InstanceStub
{
    public function execute()
    {
        return 'foo';
    }
}
