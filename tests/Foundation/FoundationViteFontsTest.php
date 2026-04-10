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

    public function testFontsFiltersByAliasUsingManifestFamilyStyles()
    {
        $this->makeFontsManifest([
            'version' => 1,
            'style' => [
                'file' => 'assets/fonts-abc123.css',
                'familyStyles' => [
                    'sans' => "@font-face { font-family: \"Inter\"; src: url('inter.woff2'); }\n\n@font-face { font-family: \"Inter fallback\"; src: local(\"Arial\"); }",
                    'mono' => "@font-face { font-family: \"JetBrains Mono\"; src: url('jb.woff2'); }",
                ],
                'variables' => ":root {\n  --font-sans: \"Inter\", \"Inter fallback\";\n  --font-mono: \"JetBrains Mono\";\n}",
            ],
            'preloads' => [
                [
                    'alias' => 'sans',
                    'family' => 'Inter',
                    'weight' => 400,
                    'style' => 'normal',
                    'file' => 'assets/inter-400.woff2',
                    'as' => 'font',
                    'type' => 'font/woff2',
                    'crossorigin' => 'anonymous',
                ],
                [
                    'alias' => 'mono',
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
                'sans' => ['family' => 'Inter', 'variable' => '--font-sans'],
                'mono' => ['family' => 'JetBrains Mono', 'variable' => '--font-mono'],
            ],
        ]);
        $this->makeFontsCssFile('build', 'assets/fonts-abc123.css', 'full-css-not-used-during-filtering');

        $result = app(Vite::class)->fonts(['sans'])->toHtml();

        $this->assertStringContainsString('inter-400.woff2', $result);
        $this->assertStringNotContainsString('jetbrains-400.woff2', $result);
        $this->assertStringContainsString('font-family: "Inter"', $result);
        $this->assertStringContainsString('font-family: "Inter fallback"', $result);
        $this->assertStringNotContainsString('font-family: "JetBrains Mono"', $result);
        $this->assertStringContainsString(':root {', $result);
        $this->assertStringContainsString('--font-sans:', $result);
        $this->assertStringNotContainsString('--font-mono:', $result);
        $this->assertStringNotContainsString('full-css-not-used-during-filtering', $result);
    }

    public function testFontsFilteredByAliasDoesNotThrowForMalformedPreloadOfOtherAlias()
    {
        $this->makeFontsManifest([
            'version' => 1,
            'style' => [
                'inline' => "@font-face { font-family: 'Inter'; }",
                'familyStyles' => [
                    'sans' => "@font-face { font-family: 'Inter'; }",
                ],
                'variables' => '',
            ],
            'preloads' => [
                [
                    'alias' => 'sans',
                    'family' => 'Inter',
                    'weight' => 400,
                    'style' => 'normal',
                    'file' => 'assets/inter-400.woff2',
                    'as' => 'font',
                    'type' => 'font/woff2',
                    'crossorigin' => 'anonymous',
                ],
                [
                    'alias' => 'broken',
                    'family' => 'Broken',
                    'as' => 'font',
                ],
            ],
            'families' => [
                'sans' => ['family' => 'Inter', 'variable' => '--font-sans'],
                'broken' => ['family' => 'Broken', 'variable' => '--font-broken'],
            ],
        ]);

        $result = app(Vite::class)->fonts(['sans'])->toHtml();

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

    public function testFontsAcceptsStringAlias()
    {
        $this->makeFontsManifest([
            'version' => 1,
            'style' => [
                'inline' => "@font-face { font-family: 'Inter'; }",
                'familyStyles' => [
                    'sans' => "@font-face { font-family: 'Inter'; }",
                ],
                'variables' => '',
            ],
            'preloads' => [],
            'families' => [
                'sans' => ['family' => 'Inter', 'variable' => '--font-sans'],
            ],
        ]);

        $result = app(Vite::class)->fonts('sans')->toHtml();

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
        $this->expectExceptionMessage('Unsupported font manifest version [99]. Supported versions: 1.');

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

    public function testUnknownRequestedAliasThrowsException()
    {
        $this->makeFontsManifest([
            'version' => 1,
            'style' => ['inline' => ''],
            'preloads' => [],
            'families' => [
                'sans' => ['family' => 'Inter', 'variable' => '--font-sans'],
            ],
        ]);

        $this->expectException(ViteException::class);
        $this->expectExceptionMessage('Font alias [display] is not defined in the font manifest. Available aliases: sans.');

        app(Vite::class)->fonts(['display']);
    }

    public function testMalformedPreloadEntryMissingAliasThrowsException()
    {
        $this->makeFontsManifest([
            'version' => 1,
            'style' => ['inline' => ''],
            'preloads' => [
                ['family' => 'Inter', 'file' => 'assets/font.woff2', 'as' => 'font'],
            ],
            'families' => [
                'sans' => ['family' => 'Inter', 'variable' => '--font-sans'],
            ],
        ]);

        $this->expectException(ViteException::class);
        $this->expectExceptionMessage('preload entry [0] is missing the [alias] key');

        app(Vite::class)->fonts();
    }

    public function testMalformedPreloadEntryMissingFileInBuildModeThrowsException()
    {
        $this->makeFontsManifest([
            'version' => 1,
            'style' => ['inline' => ''],
            'preloads' => [
                ['alias' => 'sans', 'family' => 'Inter', 'as' => 'font'],
            ],
            'families' => [
                'sans' => ['family' => 'Inter', 'variable' => '--font-sans'],
            ],
        ]);

        $this->expectException(ViteException::class);
        $this->expectExceptionMessage('preload entry [0] for alias [sans] is missing the [file] key');

        app(Vite::class)->fonts();
    }

    public function testMalformedPreloadEntryMissingUrlInHotModeThrowsException()
    {
        $this->makeHotFile();
        $this->makeHotFontsManifest([
            'version' => 1,
            'style' => ['inline' => ''],
            'preloads' => [
                ['alias' => 'sans', 'family' => 'Inter', 'as' => 'font'],
            ],
            'families' => [
                'sans' => ['family' => 'Inter', 'variable' => '--font-sans'],
            ],
        ]);

        $this->expectException(ViteException::class);
        $this->expectExceptionMessage('preload entry [0] for alias [sans] is missing the [url] key');

        app(Vite::class)->fonts();
    }

    public function testMultiplePreloadsRenderedForMultipleWeights()
    {
        $this->makeFontsManifest([
            'version' => 1,
            'style' => ['inline' => ''],
            'preloads' => [
                [
                    'alias' => 'sans',
                    'family' => 'Inter',
                    'weight' => 400,
                    'style' => 'normal',
                    'file' => 'assets/inter-400.woff2',
                    'as' => 'font',
                    'type' => 'font/woff2',
                    'crossorigin' => 'anonymous',
                ],
                [
                    'alias' => 'sans',
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
                'sans' => ['family' => 'Inter', 'variable' => '--font-sans'],
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
                'sans' => ['family' => 'Inter', 'variable' => '--font-sans'],
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
                    'alias' => 'sans',
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
                'sans' => ['family' => 'Inter', 'variable' => '--font-sans'],
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
                'sans' => ['family' => 'Inter', 'variable' => '--font-sans'],
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

    public function testFontsRendersUtilityClassInBuildMode()
    {
        $this->makeFontsManifest([
            'version' => 1,
            'style' => [
                'file' => 'assets/fonts-abc123.css',
                'familyStyles' => [
                    'sans' => "@font-face { font-family: \"Inter\"; src: url('inter.woff2'); }\n\n.font-sans {\n  font-family: var(--font-sans);\n}",
                ],
                'variables' => ':root { --font-sans: "Inter"; }',
            ],
            'preloads' => [
                ['alias' => 'sans', 'family' => 'Inter', 'weight' => 400, 'style' => 'normal', 'file' => 'assets/inter-400.woff2', 'as' => 'font', 'type' => 'font/woff2', 'crossorigin' => 'anonymous'],
            ],
            'families' => [
                'sans' => ['family' => 'Inter', 'variable' => '--font-sans'],
            ],
        ]);
        $this->makeFontsCssFile('build', 'assets/fonts-abc123.css', "@font-face { font-family: \"Inter\"; }\n\n.font-sans { font-family: var(--font-sans); }");

        $result = app(Vite::class)->fonts()->toHtml();

        $this->assertStringContainsString('.font-sans', $result);
        $this->assertStringContainsString('font-family: var(--font-sans)', $result);
    }

    public function testFontsRendersUtilityClassInHotMode()
    {
        $this->makeHotFile();
        $this->makeHotFontsManifest([
            'version' => 1,
            'style' => [
                'inline' => "@font-face { font-family: \"Inter\"; }\n\n.font-sans {\n  font-family: var(--font-sans);\n}",
                'familyStyles' => [
                    'sans' => "@font-face { font-family: \"Inter\"; }\n\n.font-sans {\n  font-family: var(--font-sans);\n}",
                ],
                'variables' => ':root { --font-sans: "Inter"; }',
            ],
            'preloads' => [
                ['alias' => 'sans', 'family' => 'Inter', 'weight' => 400, 'style' => 'normal', 'url' => 'http://localhost:3000/__laravel_vite_plugin__/fonts/inter.woff2', 'as' => 'font', 'type' => 'font/woff2', 'crossorigin' => 'anonymous'],
            ],
            'families' => [
                'sans' => ['family' => 'Inter', 'variable' => '--font-sans'],
            ],
        ]);

        $result = app(Vite::class)->fonts()->toHtml();

        $this->assertStringContainsString('.font-sans', $result);
        $this->assertStringContainsString('font-family: var(--font-sans)', $result);
    }

    public function testFontsFilteredByAliasIncludesUtilityClass()
    {
        $this->makeFontsManifest([
            'version' => 1,
            'style' => [
                'file' => 'assets/fonts-abc123.css',
                'familyStyles' => [
                    'sans' => "@font-face { font-family: \"Inter\"; }\n\n.font-sans {\n  font-family: var(--font-sans);\n}",
                    'heading' => "@font-face { font-family: \"Roboto\"; }\n\n.font-heading {\n  font-family: var(--font-heading);\n}",
                ],
                'variables' => ":root {\n  --font-sans: \"Inter\";\n  --font-heading: \"Roboto\";\n}",
            ],
            'preloads' => [
                ['alias' => 'sans', 'family' => 'Inter', 'weight' => 400, 'style' => 'normal', 'file' => 'assets/inter-400.woff2', 'as' => 'font', 'type' => 'font/woff2', 'crossorigin' => 'anonymous'],
                ['alias' => 'heading', 'family' => 'Roboto', 'weight' => 400, 'style' => 'normal', 'file' => 'assets/roboto-400.woff2', 'as' => 'font', 'type' => 'font/woff2', 'crossorigin' => 'anonymous'],
            ],
            'families' => [
                'sans' => ['family' => 'Inter', 'variable' => '--font-sans'],
                'heading' => ['family' => 'Roboto', 'variable' => '--font-heading'],
            ],
        ]);
        $this->makeFontsCssFile('build', 'assets/fonts-abc123.css', 'full-css');

        $result = app(Vite::class)->fonts(['sans'])->toHtml();

        $this->assertStringContainsString('.font-sans', $result);
        $this->assertStringNotContainsString('.font-heading', $result);
    }

    public function testFontsFiltersByMultipleAliases()
    {
        $this->makeFontsManifest([
            'version' => 1,
            'style' => [
                'file' => 'assets/fonts-abc123.css',
                'familyStyles' => [
                    'sans' => '@font-face { font-family: "Inter"; }',
                    'mono' => '@font-face { font-family: "JetBrains Mono"; }',
                    'heading' => '@font-face { font-family: "Playfair Display"; }',
                ],
                'variables' => ":root {\n  --font-sans: \"Inter\";\n  --font-mono: \"JetBrains Mono\";\n  --font-heading: \"Playfair Display\";\n}",
            ],
            'preloads' => [
                ['alias' => 'sans', 'family' => 'Inter', 'weight' => 400, 'style' => 'normal', 'file' => 'assets/inter-400.woff2', 'as' => 'font', 'type' => 'font/woff2', 'crossorigin' => 'anonymous'],
                ['alias' => 'mono', 'family' => 'JetBrains Mono', 'weight' => 400, 'style' => 'normal', 'file' => 'assets/jb-400.woff2', 'as' => 'font', 'type' => 'font/woff2', 'crossorigin' => 'anonymous'],
                ['alias' => 'heading', 'family' => 'Playfair Display', 'weight' => 400, 'style' => 'normal', 'file' => 'assets/playfair-400.woff2', 'as' => 'font', 'type' => 'font/woff2', 'crossorigin' => 'anonymous'],
            ],
            'families' => [
                'sans' => ['family' => 'Inter', 'variable' => '--font-sans'],
                'mono' => ['family' => 'JetBrains Mono', 'variable' => '--font-mono'],
                'heading' => ['family' => 'Playfair Display', 'variable' => '--font-heading'],
            ],
        ]);
        $this->makeFontsCssFile('build', 'assets/fonts-abc123.css', 'full-css');

        $result = app(Vite::class)->fonts(['sans', 'mono'])->toHtml();

        $this->assertStringContainsString('inter-400.woff2', $result);
        $this->assertStringContainsString('jb-400.woff2', $result);
        $this->assertStringNotContainsString('playfair-400.woff2', $result);
        $this->assertStringContainsString('font-family: "Inter"', $result);
        $this->assertStringContainsString('font-family: "JetBrains Mono"', $result);
        $this->assertStringNotContainsString('Playfair Display', $result);
        $this->assertStringContainsString('--font-sans:', $result);
        $this->assertStringContainsString('--font-mono:', $result);
        $this->assertStringNotContainsString('--font-heading:', $result);
    }

    public function testFontsFiltersByAliasWithSingleLineVariables()
    {
        $this->makeFontsManifest([
            'version' => 1,
            'style' => [
                'file' => 'assets/fonts-abc123.css',
                'familyStyles' => [
                    'sans' => "@font-face { font-family: \"Inter\"; }",
                    'mono' => "@font-face { font-family: \"JetBrains Mono\"; }",
                ],
                'variables' => ':root { --font-sans: "Inter", "Inter fallback"; --font-mono: "JetBrains Mono"; }',
            ],
            'preloads' => [
                ['alias' => 'sans', 'family' => 'Inter', 'weight' => 400, 'style' => 'normal', 'file' => 'assets/inter-400.woff2', 'as' => 'font', 'type' => 'font/woff2', 'crossorigin' => 'anonymous'],
                ['alias' => 'mono', 'family' => 'JetBrains Mono', 'weight' => 400, 'style' => 'normal', 'file' => 'assets/jb-400.woff2', 'as' => 'font', 'type' => 'font/woff2', 'crossorigin' => 'anonymous'],
            ],
            'families' => [
                'sans' => ['family' => 'Inter', 'variable' => '--font-sans'],
                'mono' => ['family' => 'JetBrains Mono', 'variable' => '--font-mono'],
            ],
        ]);
        $this->makeFontsCssFile('build', 'assets/fonts-abc123.css', 'full-css');

        $result = app(Vite::class)->fonts(['sans'])->toHtml();

        $this->assertStringContainsString('--font-sans:', $result);
        $this->assertStringNotContainsString('--font-mono:', $result);
    }

    public function testFontsFiltersByAliasWithMinifiedVariables()
    {
        $this->makeFontsManifest([
            'version' => 1,
            'style' => [
                'file' => 'assets/fonts-abc123.css',
                'familyStyles' => [
                    'sans' => "@font-face { font-family: \"Inter\"; }",
                    'mono' => "@font-face { font-family: \"JetBrains Mono\"; }",
                ],
                'variables' => ':root{--font-sans:"Inter","Inter fallback";--font-mono:"JetBrains Mono";}',
            ],
            'preloads' => [
                ['alias' => 'sans', 'family' => 'Inter', 'weight' => 400, 'style' => 'normal', 'file' => 'assets/inter-400.woff2', 'as' => 'font', 'type' => 'font/woff2', 'crossorigin' => 'anonymous'],
                ['alias' => 'mono', 'family' => 'JetBrains Mono', 'weight' => 400, 'style' => 'normal', 'file' => 'assets/jb-400.woff2', 'as' => 'font', 'type' => 'font/woff2', 'crossorigin' => 'anonymous'],
            ],
            'families' => [
                'sans' => ['family' => 'Inter', 'variable' => '--font-sans'],
                'mono' => ['family' => 'JetBrains Mono', 'variable' => '--font-mono'],
            ],
        ]);
        $this->makeFontsCssFile('build', 'assets/fonts-abc123.css', 'full-css');

        $result = app(Vite::class)->fonts(['sans'])->toHtml();

        $this->assertStringContainsString('--font-sans:', $result);
        $this->assertStringNotContainsString('--font-mono:', $result);
    }

    public function testFontsDuplicateFamilyWithDifferentAliasesRenderIndependently()
    {
        $this->makeFontsManifest([
            'version' => 1,
            'style' => [
                'file' => 'assets/fonts-abc123.css',
                'familyStyles' => [
                    'sans' => '@font-face { font-family: "Inter"; font-weight: 400; }',
                    'heading' => '@font-face { font-family: "Inter"; font-weight: 700; }',
                ],
                'variables' => ":root {\n  --font-sans: \"Inter\";\n  --font-heading: \"Inter\";\n}",
            ],
            'preloads' => [
                ['alias' => 'sans', 'family' => 'Inter', 'weight' => 400, 'style' => 'normal', 'file' => 'assets/inter-400.woff2', 'as' => 'font', 'type' => 'font/woff2', 'crossorigin' => 'anonymous'],
                ['alias' => 'heading', 'family' => 'Inter', 'weight' => 700, 'style' => 'normal', 'file' => 'assets/inter-700.woff2', 'as' => 'font', 'type' => 'font/woff2', 'crossorigin' => 'anonymous'],
            ],
            'families' => [
                'sans' => ['family' => 'Inter', 'variable' => '--font-sans'],
                'heading' => ['family' => 'Inter', 'variable' => '--font-heading'],
            ],
        ]);
        $this->makeFontsCssFile('build', 'assets/fonts-abc123.css', 'full-css');

        $result = app(Vite::class)->fonts(['sans'])->toHtml();

        $this->assertStringContainsString('inter-400.woff2', $result);
        $this->assertStringNotContainsString('inter-700.woff2', $result);
        $this->assertStringContainsString('font-weight: 400', $result);
        $this->assertStringNotContainsString('font-weight: 700', $result);
    }

    public function testFontsRendersUtilityClassWithCustomAlias()
    {
        $this->makeFontsManifest([
            'version' => 1,
            'style' => [
                'file' => 'assets/fonts-abc123.css',
                'familyStyles' => [
                    'sans' => "@font-face { font-family: \"Inter\"; }\n\n.font-sans {\n  font-family: var(--font-sans);\n}",
                ],
                'variables' => ':root { --font-sans: "Inter"; }',
            ],
            'preloads' => [
                ['alias' => 'sans', 'family' => 'Inter', 'weight' => 400, 'style' => 'normal', 'file' => 'assets/inter-400.woff2', 'as' => 'font', 'type' => 'font/woff2', 'crossorigin' => 'anonymous'],
            ],
            'families' => [
                'sans' => ['family' => 'Inter', 'variable' => '--font-sans'],
            ],
        ]);
        $this->makeFontsCssFile('build', 'assets/fonts-abc123.css', "@font-face { font-family: \"Inter\"; }\n\n.font-sans { font-family: var(--font-sans); }");

        $result = app(Vite::class)->fonts()->toHtml();

        $this->assertStringContainsString('.font-sans', $result);
        $this->assertStringContainsString('font-family: var(--font-sans)', $result);
    }

    protected function defaultManifest(): array
    {
        return [
            'version' => 1,
            'style' => [
                'file' => 'assets/fonts-abc123.css',
                'familyStyles' => [
                    'sans' => "@font-face { font-family: 'Inter'; }",
                ],
                'variables' => ':root { --font-sans: "Inter"; }',
            ],
            'preloads' => [
                [
                    'alias' => 'sans',
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
                'sans' => ['family' => 'Inter', 'variable' => '--font-sans'],
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
                    'sans' => "@font-face { font-family: 'Inter'; src: url('http://localhost:3000/fonts/inter.woff2'); }",
                ],
                'variables' => ':root { --font-sans: "Inter"; }',
            ],
            'preloads' => [
                [
                    'alias' => 'sans',
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
                'sans' => ['family' => 'Inter', 'variable' => '--font-sans'],
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
