<?php

namespace Illuminate\Tests\Integration\Foundation;

use Exception;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Orchestra\Testbench\TestCase;

class FoundationHelpersTest extends TestCase
{
    public function testBytesToHumanReadableSize()
    {
        $this->assertEquals(
            '0 B',
            bytesToHumanReadableSize(0),
        );

        $this->assertEquals(
            '1 B',
            bytesToHumanReadableSize(1),
        );

        $this->assertEquals(
            '10 B',
            bytesToHumanReadableSize(10),
        );

        $this->assertEquals(
            '1.23 KB',
            bytesToHumanReadableSize(1234),
        );

        $this->assertEquals(
            '12.35 KB',
            bytesToHumanReadableSize(12345),
        );

        $this->assertEquals(
            '123.46 KB',
            bytesToHumanReadableSize(123456),
        );

        $this->assertEquals(
            '1.23 MB',
            bytesToHumanReadableSize(1234567),
        );

        $this->assertEquals(
            '12.35 MB',
            bytesToHumanReadableSize(12345678),
        );

        $this->assertEquals(
            '123.46 MB',
            bytesToHumanReadableSize(123456789),
        );

        $this->assertEquals(
            '1.23 GB',
            bytesToHumanReadableSize(1234567890),
        );

        $this->assertEquals(
            '12.35 GB',
            bytesToHumanReadableSize(12345678901),
        );

        $this->assertEquals(
            '123.46 GB',
            bytesToHumanReadableSize(123456789012),
        );

        $this->assertEquals(
            '1.23 TB',
            bytesToHumanReadableSize(1234567890123),
        );

        $this->assertEquals(
            '1 KB',
            bytesToHumanReadableSize(1234, 0),
        );

        $this->assertEquals(
            '1.2 KB',
            bytesToHumanReadableSize(1234, 1),
        );

        $this->assertEquals(
            '1.234 KB',
            bytesToHumanReadableSize(1234, 3),
        );

        $this->assertEquals(
            '1.235 GB',
            bytesToHumanReadableSize(1234567890, 3),
        );

        $this->assertEquals(
            '1.2346 GB',
            bytesToHumanReadableSize(1234567890, 4),
        );

        $this->assertEquals(
            '1.23457 GB',
            bytesToHumanReadableSize(1234567890, 5),
        );

        $this->assertEquals(
            '1,2 KB',
            bytesToHumanReadableSize(1234, 1, "pt-BR"),
        );

        $this->assertEquals(
            '1,23 KB',
            bytesToHumanReadableSize(1234, 2, "pt-BR"),
        );

        $this->assertEquals(
            '1,234 KB',
            bytesToHumanReadableSize(1234, 3, "pt-BR"),
        );

        $this->assertEquals(
            '123,457 GB',
            bytesToHumanReadableSize(123456789012, 3, "pt-BR"),
        );

        $this->assertEquals(
            '123,457 GB',
            bytesToHumanReadableSize(123456789012, 4, "pt-BR"),
        );

        $this->assertEquals(
            '123,457 GB',
            bytesToHumanReadableSize(123456789012, 5, "pt-BR"),
        );

        $this->assertEquals(
            '123.46 GB',
            bytesToHumanReadableSize(123456789012, 2, "nonexistent"),
        );

        $this->assertEquals(
            '0 B',
            bytesToHumanReadableSize(-1),
        );

        $this->assertEquals(
            '0 B',
            bytesToHumanReadableSize("-1"),
        );

        $this->assertEquals(
            "1.23 KB",
            bytesToHumanReadableSize("1234"),
        );
    }

    public function testRescue()
    {
        $this->assertEquals(
            'rescued!',
            rescue(function () {
                throw new Exception;
            }, 'rescued!')
        );

        $this->assertEquals(
            'rescued!',
            rescue(function () {
                throw new Exception;
            }, function () {
                return 'rescued!';
            })
        );

        $this->assertEquals(
            'no need to rescue',
            rescue(function () {
                return 'no need to rescue';
            }, 'rescued!')
        );

        $testClass = new class
        {
            public function test(int $a)
            {
                return $a;
            }
        };

        $this->assertEquals(
            'rescued!',
            rescue(function () use ($testClass) {
                $testClass->test([]);
            }, 'rescued!')
        );
    }

    public function testMixReportsExceptionWhenAssetIsMissingFromManifest()
    {
        $handler = new FakeHandler;
        $this->app->instance(ExceptionHandler::class, $handler);
        $manifest = $this->makeManifest();

        mix('missing.js');

        $this->assertInstanceOf(Exception::class, $handler->reported[0]);
        $this->assertSame('Unable to locate Mix file: /missing.js.', $handler->reported[0]->getMessage());

        unlink($manifest);
    }

    public function testMixSilentlyFailsWhenAssetIsMissingFromManifestWhenNotInDebugMode()
    {
        $this->app['config']->set('app.debug', false);

        $manifest = $this->makeManifest();

        $path = mix('missing.js');

        $this->assertSame('/missing.js', $path);

        unlink($manifest);
    }

    public function testMixThrowsExceptionWhenAssetIsMissingFromManifestWhenInDebugMode()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Unable to locate Mix file: /missing.js.');

        $this->app['config']->set('app.debug', true);

        $manifest = $this->makeManifest();

        try {
            mix('missing.js');
        } catch (Exception $e) {
            throw $e;
        } finally { // make sure we can cleanup the file
            unlink($manifest);
        }
    }

    public function testMixOnlyThrowsAndReportsOneExceptionWhenAssetIsMissingFromManifestWhenInDebugMode()
    {
        $handler = new FakeHandler;
        $this->app->instance(ExceptionHandler::class, $handler);
        $this->app['config']->set('app.debug', true);

        $manifest = $this->makeManifest();

        Route::get('test-route', function () {
            mix('missing.js');
        });

        $this->get('/test-route');

        $this->assertCount(1, $handler->reported);

        unlink($manifest);
    }

    protected function makeManifest($directory = '')
    {
        $this->app->singleton('path.public', function () {
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
}

class FakeHandler
{
    public $reported = [];

    public function report($exception)
    {
        $this->reported[] = $exception;
    }

    public function render($exception)
    {
        //
    }
}
