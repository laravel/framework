<?php

namespace Illuminate\Tests\Foundation;

use Illuminate\Foundation\Vite;
use Illuminate\Foundation\ViteException;
use Illuminate\Support\Facades\Vite as ViteFacade;
use Illuminate\Support\Str;
use Orchestra\Testbench\TestCase;

class FoundationViteFontsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        app('config')->set('app.asset_url', 'https://example.com');
    }

    protected function tearDown(): void
    {
        $this->cleanFontsManifest();
        $this->cleanFontsManifest('custom-build');
        $this->cleanHotFontsManifest();
        $this->cleanHotFontsManifest(__DIR__.'/custom-hot-dir');
        $this->cleanHotFile();
        $this->cleanHotFile(__DIR__.'/custom-hot-dir/hot');
        app(Vite::class)->flush();

        parent::tearDown();
    }

    public function testFontsReturnsEmptyStringWhenNoManifestExists()
    {
        app()->usePublicPath(__DIR__);

        $result = app(Vite::class)->fonts();

        $this->assertSame('', $result->toHtml());
    }

    public function testFontsReturnsEmptyStringWhenHotFileExistsButNoHotManifest()
    {
        $this->makeHotFile();

        $result = app(Vite::class)->fonts();

        $this->assertSame('', $result->toHtml());
    }

    public function testFontsRendersPreloadsAndStyleInBuildMode()
    {
        $this->makeFontsManifest();
        $this->makeFontsCssFile('build', 'assets/fonts-abc123.css', "@font-face { font-family: 'Inter'; src: url('../fonts/inter-400.woff2') format('woff2'); }");

        $result = app(Vite::class)->fonts();

        $this->assertStringContainsString(
            '<link rel="preload" as="font" href="https://example.com/build/assets/inter-400.woff2" type="font/woff2" crossorigin="anonymous" />',
            $result->toHtml()
        );
        $this->assertStringContainsString(
            "<style>@font-face { font-family: 'Inter'; src: url('../fonts/inter-400.woff2') format('woff2'); }</style>",
            $result->toHtml()
        );
    }

    public function testFontsRendersPreloadsBeforeStyle()
    {
        $this->makeFontsManifest();
        $this->makeFontsCssFile('build', 'assets/fonts-abc123.css', "@font-face { font-family: 'Inter'; }");

        $result = app(Vite::class)->fonts()->toHtml();

        $preloadPos = strpos($result, '<link rel="preload"');
        $stylePos = strpos($result, '<style>');

        $this->assertNotFalse($preloadPos);
        $this->assertNotFalse($stylePos);
        $this->assertLessThan($stylePos, $preloadPos);
    }

    public function testFontsRendersInHotMode()
    {
        $this->makeHotFile();
        $this->makeHotFontsManifest();

        $result = app(Vite::class)->fonts();

        $this->assertStringContainsString(
            '<link rel="preload" as="font" href="http://localhost:3000/__laravel_vite_plugin__/fonts/inter.woff2" type="font/woff2" crossorigin="anonymous" />',
            $result->toHtml()
        );
        $this->assertStringContainsString(
            "<style>@font-face { font-family: 'Inter'; src: url('http://localhost:3000/fonts/inter.woff2'); }</style>",
            $result->toHtml()
        );
    }

    public function testFontsRespectsCustomBuildDirectory()
    {
        $this->makeFontsManifest($this->defaultManifest(), 'custom-build');
        $this->makeFontsCssFile('custom-build', 'assets/fonts-abc123.css', "@font-face { font-family: 'Inter'; }");

        ViteFacade::useBuildDirectory('custom-build');

        $result = app(Vite::class)->fonts();

        $this->assertStringContainsString(
            'href="https://example.com/custom-build/assets/inter-400.woff2"',
            $result->toHtml()
        );
    }

    public function testFontsRespectsCreateAssetPathsUsing()
    {
        $this->makeFontsManifest();
        $this->makeFontsCssFile('build', 'assets/fonts-abc123.css', "@font-face { font-family: 'Inter'; }");

        ViteFacade::createAssetPathsUsing(fn ($path) => "https://cdn.example.com/{$path}");

        $result = app(Vite::class)->fonts();

        $this->assertStringContainsString(
            'href="https://cdn.example.com/build/assets/inter-400.woff2"',
            $result->toHtml()
        );

        ViteFacade::createAssetPathsUsing(null);
    }

    public function testFontsAppliesCspNonceToStyleAndPreloads()
    {
        Str::createRandomStringsUsing(fn ($length) => "random-string-with-length:{$length}");
        $this->makeFontsManifest();
        $this->makeFontsCssFile('build', 'assets/fonts-abc123.css', "@font-face { font-family: 'Inter'; }");

        ViteFacade::useCspNonce();

        $result = app(Vite::class)->fonts()->toHtml();

        $this->assertStringContainsString('nonce="random-string-with-length:40"', $result);
        $this->assertStringContainsString('<style nonce="random-string-with-length:40">', $result);
        $this->assertStringContainsString('<link rel="preload" as="font" href="https://example.com/build/assets/inter-400.woff2" type="font/woff2" crossorigin="anonymous" nonce="random-string-with-length:40" />', $result);

        Str::createRandomStringsNormally();
    }

    public function testFontsRespectsUsePreloadTagAttributes()
    {
        $this->makeFontsManifest();
        $this->makeFontsCssFile('build', 'assets/fonts-abc123.css', "@font-face { font-family: 'Inter'; }");

        ViteFacade::usePreloadTagAttributes(fn ($src, $url, $chunk, $manifest) => [
            'data-turbo-track' => 'reload',
        ]);

        $result = app(Vite::class)->fonts()->toHtml();

        $this->assertStringContainsString('data-turbo-track="reload"', $result);
    }

    public function testFontsRespectsPreloadTagAttributesReturningFalse()
    {
        $this->makeFontsManifest();
        $this->makeFontsCssFile('build', 'assets/fonts-abc123.css', "@font-face { font-family: 'Inter'; }");

        ViteFacade::usePreloadTagAttributes(fn () => false);

        $result = app(Vite::class)->fonts()->toHtml();

        $this->assertStringNotContainsString('<link', $result);
        $this->assertStringContainsString('<style>', $result);
    }

    public function testFontsFiltersByFamilyUsingManifestFamilyStyles()
    {
        $this->makeFontsManifest([
            'version' => 1,
            'style' => [
                'file' => 'assets/fonts-abc123.css',
                'familyStyles' => [
                    'Inter' => "@font-face { font-family: \"Inter\"; src: url('inter.woff2'); }\n\n@font-face { font-family: \"Inter fallback\"; src: local(\"Arial\"); }",
                    'JetBrains Mono' => "@font-face { font-family: \"JetBrains Mono\"; src: url('jb.woff2'); }",
                ],
                'variables' => ":root {\n  --font-inter: \"Inter\", \"Inter fallback\";\n  --font-jb: \"JetBrains Mono\";\n}",
            ],
            'preloads' => [
                [
                    'family' => 'Inter',
                    'weight' => 400,
                    'style' => 'normal',
                    'file' => 'assets/inter-400.woff2',
                    'as' => 'font',
                    'type' => 'font/woff2',
                    'crossorigin' => 'anonymous',
                ],
                [
                    'family' => 'JetBrains Mono',
                    'weight' => 400,
                    'style' => 'normal',
                    'file' => 'assets/jetbrains-400.woff2',
                    'as' => 'font',
                    'type' => 'font/woff2',
                    'crossorigin' => 'anonymous',
                ],
            ],
            'families' => [
                'Inter' => ['variable' => '--font-inter'],
                'JetBrains Mono' => ['variable' => '--font-jb'],
            ],
        ]);
        $this->makeFontsCssFile('build', 'assets/fonts-abc123.css', 'full-css-not-used-during-filtering');

        $result = app(Vite::class)->fonts(['Inter'])->toHtml();

        $this->assertStringContainsString('inter-400.woff2', $result);
        $this->assertStringNotContainsString('jetbrains-400.woff2', $result);
        $this->assertStringContainsString('font-family: "Inter"', $result);
        $this->assertStringContainsString('font-family: "Inter fallback"', $result);
        $this->assertStringNotContainsString('font-family: "JetBrains Mono"', $result);
        $this->assertStringContainsString(':root {', $result);
        $this->assertStringContainsString('--font-inter:', $result);
        $this->assertStringNotContainsString('--font-jb:', $result);
        $this->assertStringNotContainsString('full-css-not-used-during-filtering', $result);
    }

    public function testFontsFilteredByFamilyDoesNotThrowForMalformedPreloadOfOtherFamily()
    {
        $this->makeFontsManifest([
            'version' => 1,
            'style' => [
                'inline' => "@font-face { font-family: 'Inter'; }",
                'familyStyles' => [
                    'Inter' => "@font-face { font-family: 'Inter'; }",
                ],
                'variables' => '',
            ],
            'preloads' => [
                [
                    'family' => 'Inter',
                    'weight' => 400,
                    'style' => 'normal',
                    'file' => 'assets/inter-400.woff2',
                    'as' => 'font',
                    'type' => 'font/woff2',
                    'crossorigin' => 'anonymous',
                ],
                [
                    'family' => 'Broken',
                    'as' => 'font',
                ],
            ],
            'families' => [
                'Inter' => ['variable' => '--font-inter'],
                'Broken' => ['variable' => '--font-broken'],
            ],
        ]);

        $result = app(Vite::class)->fonts(['Inter'])->toHtml();

        $this->assertStringContainsString('inter-400.woff2', $result);
    }

    public function testFontsPreloadCallbackReceivesStableSourceIdentifier()
    {
        $this->makeFontsManifest();
        $this->makeFontsCssFile('build', 'assets/fonts-abc123.css', "@font-face { font-family: 'Inter'; }");

        $receivedSrc = null;

        ViteFacade::usePreloadTagAttributes(function ($src, $url, $chunk, $manifest) use (&$receivedSrc) {
            $receivedSrc = $src;

            return [];
        });

        app(Vite::class)->fonts();

        $this->assertSame('fonts', $receivedSrc);
    }

    public function testFontsAcceptsStringFamily()
    {
        $this->makeFontsManifest([
            'version' => 1,
            'style' => [
                'inline' => "@font-face { font-family: 'Inter'; }",
                'familyStyles' => [
                    'Inter' => "@font-face { font-family: 'Inter'; }",
                ],
                'variables' => '',
            ],
            'preloads' => [],
            'families' => [
                'Inter' => [],
            ],
        ]);

        $result = app(Vite::class)->fonts('Inter')->toHtml();

        $this->assertStringContainsString("font-family: 'Inter'", $result);
    }

    public function testFontsRecordsPreloadedAssets()
    {
        $this->makeFontsManifest();
        $this->makeFontsCssFile('build', 'assets/fonts-abc123.css', "@font-face { font-family: 'Inter'; }");

        app(Vite::class)->fonts();

        $preloaded = app(Vite::class)->preloadedAssets();

        $this->assertArrayHasKey('https://example.com/build/assets/inter-400.woff2', $preloaded);
    }

    public function testFontsDoesNotDuplicatePreloadedAssets()
    {
        $this->makeFontsManifest();
        $this->makeFontsCssFile('build', 'assets/fonts-abc123.css', "@font-face { font-family: 'Inter'; }");

        $vite = app(Vite::class);

        $first = $vite->fonts()->toHtml();
        $second = $vite->fonts()->toHtml();

        $this->assertSame(1, substr_count($first, '<link'));
        $this->assertSame(0, substr_count($second, '<link'));
    }

    public function testMalformedManifestThrowsException()
    {
        app()->usePublicPath(__DIR__);

        $buildPath = public_path('build');

        if (! file_exists($buildPath)) {
            mkdir($buildPath, 0755, true);
        }

        file_put_contents(public_path('build/fonts-manifest.json'), 'not-valid-json{');

        $this->expectException(ViteException::class);
        $this->expectExceptionMessage('not valid JSON');

        app(Vite::class)->fonts();
    }

    public function testUnsupportedManifestVersionThrowsException()
    {
        $this->makeFontsManifest(['version' => 99, 'families' => []]);

        $this->expectException(ViteException::class);
        $this->expectExceptionMessage('Unsupported font manifest version [99]');

        app(Vite::class)->fonts();
    }

    public function testMissingManifestVersionThrowsException()
    {
        $this->makeFontsManifest(['style' => ['inline' => ''], 'families' => []]);

        $this->expectException(ViteException::class);
        $this->expectExceptionMessage('missing the [version] key');

        app(Vite::class)->fonts();
    }

    public function testMissingFamiliesKeyThrowsException()
    {
        $this->makeFontsManifest(['version' => 1]);

        $this->expectException(ViteException::class);
        $this->expectExceptionMessage('missing the [families] key');

        app(Vite::class)->fonts();
    }

    public function testMissingCssFileThrowsException()
    {
        $this->makeFontsManifest();

        $this->expectException(ViteException::class);
        $this->expectExceptionMessage('Unable to locate font CSS file');

        app(Vite::class)->fonts();
    }

    public function testUnknownRequestedFamilyThrowsException()
    {
        $this->makeFontsManifest([
            'version' => 1,
            'style' => ['inline' => ''],
            'preloads' => [],
            'families' => [
                'Inter' => [],
            ],
        ]);

        $this->expectException(ViteException::class);
        $this->expectExceptionMessage('Font family [Roboto] is not defined in the font manifest. Available families: Inter.');

        app(Vite::class)->fonts(['Roboto']);
    }

    public function testMalformedPreloadEntryMissingFamilyThrowsException()
    {
        $this->makeFontsManifest([
            'version' => 1,
            'style' => ['inline' => ''],
            'preloads' => [
                ['file' => 'assets/font.woff2', 'as' => 'font'],
            ],
            'families' => [
                'Inter' => [],
            ],
        ]);

        $this->expectException(ViteException::class);
        $this->expectExceptionMessage('preload entry [0] is missing the [family] key');

        app(Vite::class)->fonts();
    }

    public function testMalformedPreloadEntryMissingFileInBuildModeThrowsException()
    {
        $this->makeFontsManifest([
            'version' => 1,
            'style' => ['inline' => ''],
            'preloads' => [
                ['family' => 'Inter', 'as' => 'font'],
            ],
            'families' => [
                'Inter' => [],
            ],
        ]);

        $this->expectException(ViteException::class);
        $this->expectExceptionMessage('preload entry [0] for family [Inter] is missing the [file] key');

        app(Vite::class)->fonts();
    }

    public function testMalformedPreloadEntryMissingUrlInHotModeThrowsException()
    {
        $this->makeHotFile();
        $this->makeHotFontsManifest([
            'version' => 1,
            'style' => ['inline' => ''],
            'preloads' => [
                ['family' => 'Inter', 'as' => 'font'],
            ],
            'families' => [
                'Inter' => [],
            ],
        ]);

        $this->expectException(ViteException::class);
        $this->expectExceptionMessage('preload entry [0] for family [Inter] is missing the [url] key');

        app(Vite::class)->fonts();
    }

    public function testMultiplePreloadsRenderedForMultipleWeights()
    {
        $this->makeFontsManifest([
            'version' => 1,
            'style' => ['inline' => ''],
            'preloads' => [
                [
                    'family' => 'Inter',
                    'weight' => 400,
                    'style' => 'normal',
                    'file' => 'assets/inter-400.woff2',
                    'as' => 'font',
                    'type' => 'font/woff2',
                    'crossorigin' => 'anonymous',
                ],
                [
                    'family' => 'Inter',
                    'weight' => 700,
                    'style' => 'normal',
                    'file' => 'assets/inter-700.woff2',
                    'as' => 'font',
                    'type' => 'font/woff2',
                    'crossorigin' => 'anonymous',
                ],
            ],
            'families' => [
                'Inter' => [],
            ],
        ]);

        $result = app(Vite::class)->fonts()->toHtml();

        $this->assertSame(2, substr_count($result, '<link'));
        $this->assertStringContainsString('inter-400.woff2', $result);
        $this->assertStringContainsString('inter-700.woff2', $result);
    }

    public function testFontsOutputIsDeterministic()
    {
        $this->makeFontsManifest();
        $this->makeFontsCssFile('build', 'assets/fonts-abc123.css', "@font-face { font-family: 'Inter'; }");

        $first = app(Vite::class)->fonts()->toHtml();

        app(Vite::class)->flush();

        $second = app(Vite::class)->fonts()->toHtml();

        $this->assertSame($first, $second);
    }

    public function testFontsWithNoPreloadsStillRendersStyle()
    {
        $this->makeFontsManifest([
            'version' => 1,
            'style' => ['inline' => "@font-face { font-family: 'Inter'; }"],
            'preloads' => [],
            'families' => [
                'Inter' => [],
            ],
        ]);

        $result = app(Vite::class)->fonts()->toHtml();

        $this->assertStringNotContainsString('<link', $result);
        $this->assertStringContainsString("<style>@font-face { font-family: 'Inter'; }</style>", $result);
    }

    public function testFontsWithNoStyleStillRendersPreloads()
    {
        $this->makeFontsManifest([
            'version' => 1,
            'preloads' => [
                [
                    'family' => 'Inter',
                    'weight' => 400,
                    'style' => 'normal',
                    'file' => 'assets/inter-400.woff2',
                    'as' => 'font',
                    'type' => 'font/woff2',
                    'crossorigin' => 'anonymous',
                ],
            ],
            'families' => [
                'Inter' => [],
            ],
        ]);

        $result = app(Vite::class)->fonts()->toHtml();

        $this->assertStringContainsString('<link', $result);
        $this->assertStringNotContainsString('<style', $result);
    }

    public function testFontsFlushClearsPreloadedAssetsButPreservesConfiguration()
    {
        $this->makeFontsManifest();
        $this->makeFontsCssFile('build', 'assets/fonts-abc123.css', "@font-face { font-family: 'Inter'; }");

        $vite = app(Vite::class);
        $vite->useFontsManifestFilename('fonts-manifest.json');
        $vite->fonts();

        $this->assertNotEmpty($vite->preloadedAssets());

        $vite->flush();

        $this->assertEmpty($vite->preloadedAssets());

        $result = $vite->fonts()->toHtml();
        $this->assertStringContainsString('<link', $result);
    }

    public function testHotManifestPathDerivesFromHotFile()
    {
        app()->usePublicPath(__DIR__);

        $customHotDir = __DIR__.'/custom-hot-dir';

        if (! file_exists($customHotDir)) {
            mkdir($customHotDir, 0755, true);
        }

        file_put_contents($customHotDir.'/hot', 'http://localhost:3000');

        $manifest = json_encode([
            'version' => 1,
            'style' => [
                'inline' => "@font-face { font-family: 'Inter'; }",
            ],
            'preloads' => [],
            'families' => [
                'Inter' => [],
            ],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        file_put_contents($customHotDir.'/fonts-manifest.dev.json', $manifest);

        ViteFacade::useHotFile($customHotDir.'/hot');

        $result = app(Vite::class)->fonts()->toHtml();

        $this->assertStringContainsString("font-family: 'Inter'", $result);
    }

    public function testHotManifestNotFoundWithCustomHotFileReturnsEmpty()
    {
        app()->usePublicPath(__DIR__);

        $customHotDir = __DIR__.'/custom-hot-dir';

        if (! file_exists($customHotDir)) {
            mkdir($customHotDir, 0755, true);
        }

        file_put_contents($customHotDir.'/hot', 'http://localhost:3000');

        ViteFacade::useHotFile($customHotDir.'/hot');

        $result = app(Vite::class)->fonts()->toHtml();

        $this->assertSame('', $result);
    }

    protected function defaultManifest(): array
    {
        return [
            'version' => 1,
            'style' => [
                'file' => 'assets/fonts-abc123.css',
                'familyStyles' => [
                    'Inter' => "@font-face { font-family: 'Inter'; }",
                ],
                'variables' => ':root { --font-inter: "Inter"; }',
            ],
            'preloads' => [
                [
                    'family' => 'Inter',
                    'weight' => 400,
                    'style' => 'normal',
                    'file' => 'assets/inter-400.woff2',
                    'as' => 'font',
                    'type' => 'font/woff2',
                    'crossorigin' => 'anonymous',
                ],
            ],
            'families' => [
                'Inter' => [],
            ],
        ];
    }

    protected function defaultHotManifest(): array
    {
        return [
            'version' => 1,
            'style' => [
                'inline' => "@font-face { font-family: 'Inter'; src: url('http://localhost:3000/fonts/inter.woff2'); }",
                'familyStyles' => [
                    'Inter' => "@font-face { font-family: 'Inter'; src: url('http://localhost:3000/fonts/inter.woff2'); }",
                ],
                'variables' => ':root { --font-inter: "Inter"; }',
            ],
            'preloads' => [
                [
                    'family' => 'Inter',
                    'weight' => 400,
                    'style' => 'normal',
                    'url' => 'http://localhost:3000/__laravel_vite_plugin__/fonts/inter.woff2',
                    'as' => 'font',
                    'type' => 'font/woff2',
                    'crossorigin' => 'anonymous',
                ],
            ],
            'families' => [
                'Inter' => [],
            ],
        ];
    }

    protected function makeFontsManifest(?array $contents = null, string $buildDir = 'build'): void
    {
        app()->usePublicPath(__DIR__);

        $dir = public_path($buildDir);

        if (! file_exists($dir)) {
            mkdir($dir, 0755, true);
        }

        $manifest = json_encode($contents ?? $this->defaultManifest(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        file_put_contents(public_path("{$buildDir}/fonts-manifest.json"), $manifest);
    }

    protected function makeFontsCssFile(string $buildDir, string $file, string $content): void
    {
        app()->usePublicPath(__DIR__);

        $dir = public_path($buildDir.'/assets');

        if (! file_exists($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents(public_path("{$buildDir}/{$file}"), $content);
    }

    protected function makeHotFile(?string $path = null): void
    {
        app()->usePublicPath(__DIR__);

        $path ??= public_path('hot');

        $dir = dirname($path);

        if (! file_exists($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($path, 'http://localhost:3000');
    }

    protected function makeHotFontsManifest(?array $contents = null, ?string $dir = null): void
    {
        app()->usePublicPath(__DIR__);

        $dir ??= __DIR__;

        if (! file_exists($dir)) {
            mkdir($dir, 0755, true);
        }

        $manifest = json_encode($contents ?? $this->defaultHotManifest(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        file_put_contents($dir.'/fonts-manifest.dev.json', $manifest);
    }

    protected function cleanFontsManifest(string $buildDir = 'build'): void
    {
        $cssFile = public_path("{$buildDir}/assets/fonts-abc123.css");

        if (file_exists($cssFile)) {
            unlink($cssFile);
        }

        $assetsDir = public_path("{$buildDir}/assets");

        if (is_dir($assetsDir) && count(glob("{$assetsDir}/*")) === 0) {
            rmdir($assetsDir);
        }

        $manifestFile = public_path("{$buildDir}/fonts-manifest.json");

        if (file_exists($manifestFile)) {
            unlink($manifestFile);
        }

        $dir = public_path($buildDir);

        if (is_dir($dir) && count(glob("{$dir}/*")) === 0) {
            rmdir($dir);
        }
    }

    protected function cleanHotFontsManifest(?string $dir = null): void
    {
        $dir ??= __DIR__;

        $path = $dir.'/fonts-manifest.dev.json';

        if (file_exists($path)) {
            unlink($path);
        }

        if ($dir !== __DIR__ && is_dir($dir) && count(glob("{$dir}/*")) === 0) {
            rmdir($dir);
        }
    }

    protected function cleanHotFile(?string $path = null): void
    {
        $path ??= public_path('hot');

        if (file_exists($path)) {
            unlink($path);
        }

        $dir = dirname($path);

        if ($dir !== __DIR__ && is_dir($dir) && count(glob("{$dir}/*")) === 0) {
            rmdir($dir);
        }
    }
}
