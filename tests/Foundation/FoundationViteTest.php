<?php

namespace Illuminate\Tests\Foundation;

use Illuminate\Foundation\Vite;
use Illuminate\Foundation\ViteException;
use Illuminate\Foundation\ViteManifestNotFoundException;
use Illuminate\Support\Facades\Vite as ViteFacade;
use Illuminate\Support\Js;
use Illuminate\Support\Str;
use Orchestra\Testbench\TestCase;

class FoundationViteTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        app('config')->set('app.asset_url', 'https://example.com');
    }

    protected function tearDown(): void
    {
        $this->cleanViteManifest();
        $this->cleanViteHotFile();

        parent::tearDown();
    }

    public function testViteWithJsOnly()
    {
        $this->makeViteManifest();

        $result = app(Vite::class)('resources/js/app.js');

        $this->assertStringEndsWith('<script type="module" src="https://example.com/build/assets/app.versioned.js"></script>', $result->toHtml());
    }

    public function testViteWithCssAndJs()
    {
        $this->makeViteManifest();

        $result = app(Vite::class)(['resources/css/app.css', 'resources/js/app.js']);

        $this->assertStringEndsWith(
            '<link rel="stylesheet" href="https://example.com/build/assets/app.versioned.css" />'
            .'<script type="module" src="https://example.com/build/assets/app.versioned.js"></script>',
            $result->toHtml()
        );
    }

    public function testViteWithCssImport()
    {
        $this->makeViteManifest();

        $result = app(Vite::class)('resources/js/app-with-css-import.js');

        $this->assertStringEndsWith(
            '<link rel="stylesheet" href="https://example.com/build/assets/imported-css.versioned.css" />'
            .'<script type="module" src="https://example.com/build/assets/app-with-css-import.versioned.js"></script>',
            $result->toHtml()
        );
    }

    public function testViteWithSharedCssImport()
    {
        $this->makeViteManifest();

        $result = app(Vite::class)(['resources/js/app-with-shared-css.js']);

        $this->assertStringEndsWith(
            '<link rel="stylesheet" href="https://example.com/build/assets/shared-css.versioned.css" />'
            .'<script type="module" src="https://example.com/build/assets/app-with-shared-css.versioned.js"></script>',
            $result->toHtml()
        );
    }

    public function testViteHotModuleReplacementWithJsOnly()
    {
        $this->makeViteHotFile();

        $result = app(Vite::class)('resources/js/app.js');

        $this->assertSame(
            '<script type="module" src="http://localhost:3000/@vite/client"></script>'
            .'<script type="module" src="http://localhost:3000/resources/js/app.js"></script>',
            $result->toHtml()
        );
    }

    public function testViteHotModuleReplacementWithJsAndCss()
    {
        $this->makeViteHotFile();

        $result = app(Vite::class)(['resources/css/app.css', 'resources/js/app.js']);

        $this->assertSame(
            '<script type="module" src="http://localhost:3000/@vite/client"></script>'
            .'<link rel="stylesheet" href="http://localhost:3000/resources/css/app.css" />'
            .'<script type="module" src="http://localhost:3000/resources/js/app.js"></script>',
            $result->toHtml()
        );
    }

    public function testItCanGenerateCspNonceWithHotFile()
    {
        Str::createRandomStringsUsing(fn ($length) => "random-string-with-length:{$length}");
        $this->makeViteHotFile();

        $nonce = ViteFacade::useCspNonce();
        $result = app(Vite::class)(['resources/css/app.css', 'resources/js/app.js']);

        $this->assertSame('random-string-with-length:40', $nonce);
        $this->assertSame('random-string-with-length:40', ViteFacade::cspNonce());
        $this->assertSame(
            '<script type="module" src="http://localhost:3000/@vite/client" nonce="random-string-with-length:40"></script>'
            .'<link rel="stylesheet" href="http://localhost:3000/resources/css/app.css" nonce="random-string-with-length:40" />'
            .'<script type="module" src="http://localhost:3000/resources/js/app.js" nonce="random-string-with-length:40"></script>',
            $result->toHtml()
        );

        Str::createRandomStringsNormally();
    }

    public function testItCanGenerateCspNonceWithManifest()
    {
        Str::createRandomStringsUsing(fn ($length) => "random-string-with-length:{$length}");
        $this->makeViteManifest();

        $nonce = ViteFacade::useCspNonce();
        $result = app(Vite::class)(['resources/css/app.css', 'resources/js/app.js']);

        $this->assertSame('random-string-with-length:40', $nonce);
        $this->assertSame('random-string-with-length:40', ViteFacade::cspNonce());
        $this->assertStringEndsWith(
            '<link rel="stylesheet" href="https://example.com/build/assets/app.versioned.css" nonce="random-string-with-length:40" />'
            .'<script type="module" src="https://example.com/build/assets/app.versioned.js" nonce="random-string-with-length:40"></script>',
            $result->toHtml()
        );

        Str::createRandomStringsNormally();
    }

    public function testItCanSpecifyCspNonceWithHotFile()
    {
        $this->makeViteHotFile();

        $nonce = ViteFacade::useCspNonce('expected-nonce');
        $result = app(Vite::class)(['resources/css/app.css', 'resources/js/app.js']);

        $this->assertSame('expected-nonce', $nonce);
        $this->assertSame('expected-nonce', ViteFacade::cspNonce());
        $this->assertSame(
            '<script type="module" src="http://localhost:3000/@vite/client" nonce="expected-nonce"></script>'
            .'<link rel="stylesheet" href="http://localhost:3000/resources/css/app.css" nonce="expected-nonce" />'
            .'<script type="module" src="http://localhost:3000/resources/js/app.js" nonce="expected-nonce"></script>',
            $result->toHtml()
        );
    }

    public function testItCanSpecifyCspNonceWithManifest()
    {
        $this->makeViteManifest();

        $nonce = ViteFacade::useCspNonce('expected-nonce');
        $result = app(Vite::class)(['resources/css/app.css', 'resources/js/app.js']);

        $this->assertSame('expected-nonce', $nonce);
        $this->assertSame('expected-nonce', ViteFacade::cspNonce());
        $this->assertStringEndsWith(
            '<link rel="stylesheet" href="https://example.com/build/assets/app.versioned.css" nonce="expected-nonce" />'
            .'<script type="module" src="https://example.com/build/assets/app.versioned.js" nonce="expected-nonce"></script>',
            $result->toHtml()
        );
    }

    public function testReactRefreshWithNoNonce()
    {
        $this->makeViteHotFile();

        $result = app(Vite::class)->reactRefresh();

        $this->assertStringNotContainsString('nonce', $result);
    }

    public function testReactRefreshNonce()
    {
        $this->makeViteHotFile();

        $nonce = ViteFacade::useCspNonce('expected-nonce');
        $result = app(Vite::class)->reactRefresh();

        $this->assertStringContainsString(sprintf('nonce="%s"', $nonce), $result);
    }

    public function testItCanInjectIntegrityWhenPresentInManifest()
    {
        $buildDir = Str::random();
        $this->makeViteManifest([
            'resources/js/app.js' => [
                'src' => 'resources/js/app.js',
                'file' => 'assets/app.versioned.js',
                'integrity' => 'expected-app.js-integrity',
            ],
            'resources/css/app.css' => [
                'src' => 'resources/css/app.css',
                'file' => 'assets/app.versioned.css',
                'integrity' => 'expected-app.css-integrity',
            ],
        ], $buildDir);

        $result = app(Vite::class)(['resources/css/app.css', 'resources/js/app.js'], $buildDir);

        $this->assertStringEndsWith(
            '<link rel="stylesheet" href="https://example.com/'.$buildDir.'/assets/app.versioned.css" integrity="expected-app.css-integrity" />'
            .'<script type="module" src="https://example.com/'.$buildDir.'/assets/app.versioned.js" integrity="expected-app.js-integrity"></script>',
            $result->toHtml()
        );

        $this->cleanViteManifest($buildDir);
    }

    public function testItCanInjectIntegrityWhenPresentInManifestForCss()
    {
        $buildDir = Str::random();
        $this->makeViteManifest([
            'resources/js/app.js' => [
                'src' => 'resources/js/app.js',
                'file' => 'assets/app.versioned.js',
                'css' => [
                    'assets/direct-css-dependency.aabbcc.css',
                ],
                'integrity' => 'expected-app.js-integrity',
            ],
            '_import.versioned.js' => [
                'file' => 'assets/import.versioned.js',
                'css' => [
                    'assets/imported-css.versioned.css',
                ],
                'integrity' => 'expected-import.js-integrity',
            ],
            'imported-css.css' => [
                'file' => 'assets/direct-css-dependency.aabbcc.css',
                'integrity' => 'expected-imported-css.css-integrity',
            ],
        ], $buildDir);

        $result = app(Vite::class)('resources/js/app.js', $buildDir);

        $this->assertStringEndsWith(
            '<link rel="stylesheet" href="https://example.com/'.$buildDir.'/assets/direct-css-dependency.aabbcc.css" integrity="expected-imported-css.css-integrity" />'
            .'<script type="module" src="https://example.com/'.$buildDir.'/assets/app.versioned.js" integrity="expected-app.js-integrity"></script>',
            $result->toHtml()
        );

        $this->cleanViteManifest($buildDir);
    }

    public function testItCanInjectIntegrityWhenPresentInManifestForImportedCss()
    {
        $buildDir = Str::random();
        $this->makeViteManifest([
            'resources/js/app.js' => [
                'src' => 'resources/js/app.js',
                'file' => 'assets/app.versioned.js',
                'imports' => [
                    '_import.versioned.js',
                ],
                'integrity' => 'expected-app.js-integrity',
            ],
            '_import.versioned.js' => [
                'file' => 'assets/import.versioned.js',
                'css' => [
                    'assets/imported-css.versioned.css',
                ],
                'integrity' => 'expected-import.js-integrity',
            ],
            'imported-css.css' => [
                'file' => 'assets/imported-css.versioned.css',
                'integrity' => 'expected-imported-css.css-integrity',
            ],
        ], $buildDir);

        $result = app(Vite::class)('resources/js/app.js', $buildDir);

        $this->assertStringEndsWith(
            '<link rel="stylesheet" href="https://example.com/'.$buildDir.'/assets/imported-css.versioned.css" integrity="expected-imported-css.css-integrity" />'
            .'<script type="module" src="https://example.com/'.$buildDir.'/assets/app.versioned.js" integrity="expected-app.js-integrity"></script>',
            $result->toHtml()
        );

        $this->cleanViteManifest($buildDir);
    }

    public function testItCanSpecifyIntegrityKey()
    {
        $buildDir = Str::random();
        $this->makeViteManifest([
            'resources/js/app.js' => [
                'src' => 'resources/js/app.js',
                'file' => 'assets/app.versioned.js',
                'different-integrity-key' => 'expected-app.js-integrity',
            ],
            'resources/css/app.css' => [
                'src' => 'resources/css/app.css',
                'file' => 'assets/app.versioned.css',
                'different-integrity-key' => 'expected-app.css-integrity',
            ],
        ], $buildDir);
        ViteFacade::useIntegrityKey('different-integrity-key');

        $result = app(Vite::class)(['resources/css/app.css', 'resources/js/app.js'], $buildDir);

        $this->assertStringEndsWith(
            '<link rel="stylesheet" href="https://example.com/'.$buildDir.'/assets/app.versioned.css" integrity="expected-app.css-integrity" />'
            .'<script type="module" src="https://example.com/'.$buildDir.'/assets/app.versioned.js" integrity="expected-app.js-integrity"></script>',
            $result->toHtml()
        );

        $this->cleanViteManifest($buildDir);
    }

    public function testItCanSpecifyArbitraryAttributesForScriptTagsWhenBuilt()
    {
        $this->makeViteManifest();
        ViteFacade::useScriptTagAttributes([
            'general' => 'attribute',
        ]);
        ViteFacade::useScriptTagAttributes(function ($src, $url, $chunk, $manifest) {
            $this->assertSame('resources/js/app.js', $src);
            $this->assertSame('https://example.com/build/assets/app.versioned.js', $url);
            $this->assertSame([
                'src' => 'resources/js/app.js',
                'file' => 'assets/app.versioned.js',
            ], $chunk);
            $this->assertSame([
                'resources/js/app.js' => [
                    'src' => 'resources/js/app.js',
                    'file' => 'assets/app.versioned.js',
                ],
                'resources/js/app-with-css-import.js' => [
                    'src' => 'resources/js/app-with-css-import.js',
                    'file' => 'assets/app-with-css-import.versioned.js',
                    'css' => [
                        'assets/imported-css.versioned.css',
                    ],
                ],
                'resources/css/imported-css.css' => [
                    'file' => 'assets/imported-css.versioned.css',
                ],
                'resources/js/app-with-shared-css.js' => [
                    'src' => 'resources/js/app-with-shared-css.js',
                    'file' => 'assets/app-with-shared-css.versioned.js',
                    'imports' => [
                        '_someFile.js',
                    ],
                ],
                'resources/css/app.css' => [
                    'src' => 'resources/css/app.css',
                    'file' => 'assets/app.versioned.css',
                ],
                '_someFile.js' => [
                    'file' => 'assets/someFile.versioned.js',
                    'css' => [
                        'assets/shared-css.versioned.css',
                    ],
                ],
                'resources/css/shared-css' => [
                    'src' => 'resources/css/shared-css',
                    'file' => 'assets/shared-css.versioned.css',
                ],
            ], $manifest);

            return [
                'crossorigin',
                'data-persistent-across-pages' => 'YES',
                'remove-me' => false,
                'keep-me' => true,
                'null' => null,
                'empty-string' => '',
                'zero' => 0,
            ];
        });

        $result = app(Vite::class)(['resources/css/app.css', 'resources/js/app.js']);

        $this->assertStringEndsWith(
            '<link rel="stylesheet" href="https://example.com/build/assets/app.versioned.css" />'
            .'<script type="module" src="https://example.com/build/assets/app.versioned.js" general="attribute" crossorigin data-persistent-across-pages="YES" keep-me empty-string="" zero="0"></script>',
            $result->toHtml()
        );
    }

    public function testItCanSpecifyArbitraryAttributesForStylesheetTagsWhenBuild()
    {
        $this->makeViteManifest();
        ViteFacade::useStyleTagAttributes([
            'general' => 'attribute',
        ]);
        ViteFacade::useStyleTagAttributes(function ($src, $url, $chunk, $manifest) {
            $this->assertSame('resources/css/app.css', $src);
            $this->assertSame('https://example.com/build/assets/app.versioned.css', $url);
            $this->assertSame([
                'src' => 'resources/css/app.css',
                'file' => 'assets/app.versioned.css',
            ], $chunk);
            $this->assertSame([
                'resources/js/app.js' => [
                    'src' => 'resources/js/app.js',
                    'file' => 'assets/app.versioned.js',
                ],
                'resources/js/app-with-css-import.js' => [
                    'src' => 'resources/js/app-with-css-import.js',
                    'file' => 'assets/app-with-css-import.versioned.js',
                    'css' => [
                        'assets/imported-css.versioned.css',
                    ],
                ],
                'resources/css/imported-css.css' => [
                    'file' => 'assets/imported-css.versioned.css',
                ],
                'resources/js/app-with-shared-css.js' => [
                    'src' => 'resources/js/app-with-shared-css.js',
                    'file' => 'assets/app-with-shared-css.versioned.js',
                    'imports' => [
                        '_someFile.js',
                    ],
                ],
                'resources/css/app.css' => [
                    'src' => 'resources/css/app.css',
                    'file' => 'assets/app.versioned.css',
                ],
                '_someFile.js' => [
                    'file' => 'assets/someFile.versioned.js',
                    'css' => [
                        'assets/shared-css.versioned.css',
                    ],
                ],
                'resources/css/shared-css' => [
                    'src' => 'resources/css/shared-css',
                    'file' => 'assets/shared-css.versioned.css',
                ],
            ], $manifest);

            return [
                'crossorigin',
                'data-persistent-across-pages' => 'YES',
                'remove-me' => false,
                'keep-me' => true,
            ];
        });

        $result = app(Vite::class)(['resources/css/app.css', 'resources/js/app.js']);

        $this->assertStringEndsWith(
            '<link rel="stylesheet" href="https://example.com/build/assets/app.versioned.css" general="attribute" crossorigin data-persistent-across-pages="YES" keep-me />'
            .'<script type="module" src="https://example.com/build/assets/app.versioned.js"></script>',
            $result->toHtml()
        );
    }

    public function testItCanSpecifyArbitraryAttributesForScriptTagsWhenHotModuleReloading()
    {
        $this->makeViteHotFile();
        ViteFacade::useScriptTagAttributes([
            'general' => 'attribute',
        ]);
        $expectedArguments = [
            ['src' => '@vite/client', 'url' => 'http://localhost:3000/@vite/client'],
            ['src' => 'resources/js/app.js', 'url' => 'http://localhost:3000/resources/js/app.js'],
        ];
        ViteFacade::useScriptTagAttributes(function ($src, $url, $chunk, $manifest) use (&$expectedArguments) {
            $args = array_shift($expectedArguments);

            $this->assertSame($args['src'], $src);
            $this->assertSame($args['url'], $url);
            $this->assertNull($chunk);
            $this->assertNull($manifest);

            return [
                'crossorigin',
                'data-persistent-across-pages' => 'YES',
                'remove-me' => false,
                'keep-me' => true,
            ];
        });

        $result = app(Vite::class)(['resources/css/app.css', 'resources/js/app.js']);

        $this->assertSame(
            '<script type="module" src="http://localhost:3000/@vite/client" general="attribute" crossorigin data-persistent-across-pages="YES" keep-me></script>'
            .'<link rel="stylesheet" href="http://localhost:3000/resources/css/app.css" />'
            .'<script type="module" src="http://localhost:3000/resources/js/app.js" general="attribute" crossorigin data-persistent-across-pages="YES" keep-me></script>',
            $result->toHtml()
        );
    }

    public function testItCanSpecifyArbitraryAttributesForStylesheetTagsWhenHotModuleReloading()
    {
        $this->makeViteHotFile();
        ViteFacade::useStyleTagAttributes([
            'general' => 'attribute',
        ]);
        ViteFacade::useStyleTagAttributes(function ($src, $url, $chunk, $manifest) {
            $this->assertSame('resources/css/app.css', $src);
            $this->assertSame('http://localhost:3000/resources/css/app.css', $url);
            $this->assertNull($chunk);
            $this->assertNull($manifest);

            return [
                'crossorigin',
                'data-persistent-across-pages' => 'YES',
                'remove-me' => false,
                'keep-me' => true,
            ];
        });

        $result = app(Vite::class)(['resources/css/app.css', 'resources/js/app.js']);

        $this->assertSame(
            '<script type="module" src="http://localhost:3000/@vite/client"></script>'
            .'<link rel="stylesheet" href="http://localhost:3000/resources/css/app.css" general="attribute" crossorigin data-persistent-across-pages="YES" keep-me />'
            .'<script type="module" src="http://localhost:3000/resources/js/app.js"></script>',
            $result->toHtml()
        );
    }

    public function testItCanOverrideAllAttributes()
    {
        $this->makeViteManifest();
        ViteFacade::useStyleTagAttributes([
            'rel' => 'expected-rel',
            'href' => 'expected-href',
        ]);
        ViteFacade::useScriptTagAttributes([
            'type' => 'expected-type',
            'src' => 'expected-src',
        ]);

        $result = app(Vite::class)(['resources/css/app.css', 'resources/js/app.js']);

        $this->assertStringEndsWith(
            '<link rel="expected-rel" href="expected-href" />'
            .'<script type="expected-type" src="expected-src"></script>',
            $result->toHtml()
        );
    }

    public function testItCanGenerateIndividualAssetUrlInBuildMode()
    {
        $this->makeViteManifest();

        $url = ViteFacade::asset('resources/js/app.js');

        $this->assertSame('https://example.com/build/assets/app.versioned.js', $url);
    }

    public function testItCanGenerateIndividualAssetUrlInHotMode()
    {
        $this->makeViteHotFile();

        $url = ViteFacade::asset('resources/js/app.js');

        $this->assertSame('http://localhost:3000/resources/js/app.js', $url);
    }

    public function testItThrowsWhenUnableToFindAssetManifestInBuildMode()
    {
        $this->expectException(ViteException::class);
        $this->expectExceptionMessage('Vite manifest not found at: '.public_path('build/manifest.json'));

        ViteFacade::asset('resources/js/app.js');
    }

    public function testItThrowsDeprecatedExecptionWhenUnableToFindAssetManifestInBuildMode()
    {
        $this->expectException(ViteManifestNotFoundException::class);
        $this->expectExceptionMessage('Vite manifest not found at: '.public_path('build/manifest.json'));

        ViteFacade::asset('resources/js/app.js');
    }

    public function testItThrowsWhenUnableToFindAssetChunkInBuildMode()
    {
        $this->makeViteManifest();

        $this->expectException(ViteException::class);
        $this->expectExceptionMessage('Unable to locate file in Vite manifest: resources/js/missing.js');

        ViteFacade::asset('resources/js/missing.js');
    }

    public function testItDoesNotReturnHashInDevMode()
    {
        $this->makeViteHotFile();

        $this->assertNull(ViteFacade::manifestHash());

        $this->cleanViteHotFile();
    }

    public function testItGetsHashInBuildMode()
    {
        $this->makeViteManifest(['a.js' => ['src' => 'a.js']]);

        $this->assertSame('98ca5a789544599b562c9978f3147a0f', ViteFacade::manifestHash());

        $this->cleanViteManifest();
    }

    public function testItGetsDifferentHashesForDifferentManifestsInBuildMode()
    {
        $this->makeViteManifest(['a.js' => ['src' => 'a.js']]);
        $this->makeViteManifest(['b.js' => ['src' => 'b.js']], 'admin');

        $this->assertSame('98ca5a789544599b562c9978f3147a0f', ViteFacade::manifestHash());
        $this->assertSame('928a60835978bae84e5381fbb08a38b2', ViteFacade::manifestHash('admin'));

        $this->cleanViteManifest();
        $this->cleanViteManifest('admin');
    }

    public function testViteCanSetEntryPointsWithFluentBuilder()
    {
        $this->makeViteManifest();

        $vite = app(Vite::class);

        $this->assertSame('', $vite->toHtml());

        $vite->withEntryPoints(['resources/js/app.js']);

        $this->assertStringEndsWith(
            '<script type="module" src="https://example.com/build/assets/app.versioned.js"></script>',
            $vite->toHtml()
        );
    }

    public function testViteCanOverrideBuildDirectory()
    {
        $this->makeViteManifest(null, 'custom-build');

        $vite = app(Vite::class);

        $vite->withEntryPoints(['resources/js/app.js'])->useBuildDirectory('custom-build');

        $this->assertStringEndsWith(
            '<script type="module" src="https://example.com/custom-build/assets/app.versioned.js"></script>',
            $vite->toHtml()
        );

        $this->cleanViteManifest('custom-build');
    }

    public function testViteCanOverrideHotFilePath()
    {
        $this->makeViteHotFile('cold');

        $vite = app(Vite::class);

        $vite->withEntryPoints(['resources/js/app.js'])->useHotFile('cold');

        $this->assertSame(
            '<script type="module" src="http://localhost:3000/@vite/client"></script>'
            .'<script type="module" src="http://localhost:3000/resources/js/app.js"></script>',
            $vite->toHtml()
        );

        $this->cleanViteHotFile('cold');
    }

    public function testViteCanAssetPath()
    {
        $this->makeViteManifest([
            'resources/images/profile.png' => [
                'src' => 'resources/images/profile.png',
                'file' => 'assets/profile.versioned.png',
            ],
        ], $buildDir = Str::random());
        $vite = app(Vite::class)->useBuildDirectory($buildDir);
        $this->app['config']->set('app.asset_url', 'https://cdn.app.com');

        // default behaviour...
        $this->assertSame("https://cdn.app.com/{$buildDir}/assets/profile.versioned.png", $vite->asset('resources/images/profile.png'));

        // custom behaviour
        $vite->createAssetPathsUsing(function ($path) {
            return 'https://tenant-cdn.app.com/'.$path;
        });
        $this->assertSame("https://tenant-cdn.app.com/{$buildDir}/assets/profile.versioned.png", $vite->asset('resources/images/profile.png'));

        // restore default behaviour...
        $vite->createAssetPathsUsing(null);
        $this->assertSame("https://cdn.app.com/{$buildDir}/assets/profile.versioned.png", $vite->asset('resources/images/profile.png'));

        $this->cleanViteManifest($buildDir);
    }

    public function testViteIsMacroable()
    {
        $this->makeViteManifest([
            'resources/images/profile.png' => [
                'src' => 'resources/images/profile.png',
                'file' => 'assets/profile.versioned.png',
            ],
        ], $buildDir = Str::random());
        Vite::macro('image', function ($asset, $buildDir = null) {
            return $this->asset("resources/images/{$asset}", $buildDir);
        });

        $path = ViteFacade::image('profile.png', $buildDir);

        $this->assertSame("https://example.com/{$buildDir}/assets/profile.versioned.png", $path);

        $this->cleanViteManifest($buildDir);
    }

    public function testItGeneratesPreloadDirectivesForJsAndCssImports()
    {
        $manifest = json_decode(file_get_contents(__DIR__.'/fixtures/jetstream-manifest.json'));
        $buildDir = Str::random();
        $this->makeViteManifest($manifest, $buildDir);

        $result = app(Vite::class)(['resources/js/Pages/Auth/Login.vue'], $buildDir);

        $this->assertSame(
            '<link rel="preload" as="style" href="https://example.com/'.$buildDir.'/assets/app.9842b564.css" />'
            .'<link rel="modulepreload" href="https://example.com/'.$buildDir.'/assets/Login.8c52c4a3.js" />'
            .'<link rel="modulepreload" href="https://example.com/'.$buildDir.'/assets/app.a26d8e4d.js" />'
            .'<link rel="modulepreload" href="https://example.com/'.$buildDir.'/assets/AuthenticationCard.47ef70cc.js" />'
            .'<link rel="modulepreload" href="https://example.com/'.$buildDir.'/assets/AuthenticationCardLogo.9999a373.js" />'
            .'<link rel="modulepreload" href="https://example.com/'.$buildDir.'/assets/Checkbox.33ba23f3.js" />'
            .'<link rel="modulepreload" href="https://example.com/'.$buildDir.'/assets/TextInput.e2f0248c.js" />'
            .'<link rel="modulepreload" href="https://example.com/'.$buildDir.'/assets/InputLabel.d245ec4e.js" />'
            .'<link rel="modulepreload" href="https://example.com/'.$buildDir.'/assets/PrimaryButton.931d2859.js" />'
            .'<link rel="modulepreload" href="https://example.com/'.$buildDir.'/assets/_plugin-vue_export-helper.cdc0426e.js" />'
            .'<link rel="stylesheet" href="https://example.com/'.$buildDir.'/assets/app.9842b564.css" />'
            .'<script type="module" src="https://example.com/'.$buildDir.'/assets/Login.8c52c4a3.js"></script>', $result->toHtml()
        );
        $this->assertSame([
            'https://example.com/'.$buildDir.'/assets/app.9842b564.css' => [
                'rel="preload"',
                'as="style"',
            ],
            'https://example.com/'.$buildDir.'/assets/Login.8c52c4a3.js' => [
                'rel="modulepreload"',
            ],
            'https://example.com/'.$buildDir.'/assets/app.a26d8e4d.js' => [
                'rel="modulepreload"',
            ],
            'https://example.com/'.$buildDir.'/assets/AuthenticationCard.47ef70cc.js' => [
                'rel="modulepreload"',
            ],
            'https://example.com/'.$buildDir.'/assets/AuthenticationCardLogo.9999a373.js' => [
                'rel="modulepreload"',
            ],
            'https://example.com/'.$buildDir.'/assets/Checkbox.33ba23f3.js' => [
                'rel="modulepreload"',
            ],
            'https://example.com/'.$buildDir.'/assets/TextInput.e2f0248c.js' => [
                'rel="modulepreload"',
            ],
            'https://example.com/'.$buildDir.'/assets/InputLabel.d245ec4e.js' => [
                'rel="modulepreload"',
            ],
            'https://example.com/'.$buildDir.'/assets/PrimaryButton.931d2859.js' => [
                'rel="modulepreload"',
            ],
            'https://example.com/'.$buildDir.'/assets/_plugin-vue_export-helper.cdc0426e.js' => [
                'rel="modulepreload"',
            ],
        ], ViteFacade::preloadedAssets());

        $this->cleanViteManifest($buildDir);
    }

    public function testItCanSpecifyAttributesForPreloadedAssets()
    {
        $buildDir = Str::random();
        $this->makeViteManifest([
            'resources/js/app.js' => [
                'src' => 'resources/js/app.js',
                'file' => 'assets/app.versioned.js',
                'imports' => [
                    'import.js',
                ],
                'css' => [
                    'assets/app.versioned.css',
                ],
            ],
            'import.js' => [
                'file' => 'assets/import.versioned.js',
            ],
            'resources/css/app.css' => [
                'src' => 'resources/css/app.css',
                'file' => 'assets/app.versioned.css',
            ],
        ], $buildDir);
        ViteFacade::usePreloadTagAttributes([
            'general' => 'attribute',
        ]);
        ViteFacade::usePreloadTagAttributes(function ($src, $url, $chunk, $manifest) use ($buildDir) {
            $this->assertSame([
                'resources/js/app.js' => [
                    'src' => 'resources/js/app.js',
                    'file' => 'assets/app.versioned.js',
                    'imports' => [
                        'import.js',
                    ],
                    'css' => [
                        'assets/app.versioned.css',
                    ],
                ],
                'import.js' => [
                    'file' => 'assets/import.versioned.js',
                ],
                'resources/css/app.css' => [
                    'src' => 'resources/css/app.css',
                    'file' => 'assets/app.versioned.css',
                ],
            ], $manifest);

            (match ($src) {
                'resources/js/app.js' => function () use ($url, $chunk, $buildDir) {
                    $this->assertSame("https://example.com/{$buildDir}/assets/app.versioned.js", $url);
                    $this->assertSame([
                        'src' => 'resources/js/app.js',
                        'file' => 'assets/app.versioned.js',
                        'imports' => [
                            'import.js',
                        ],
                        'css' => [
                            'assets/app.versioned.css',
                        ],
                    ], $chunk);
                },
                'import.js' => function () use ($url, $chunk, $buildDir) {
                    $this->assertSame("https://example.com/{$buildDir}/assets/import.versioned.js", $url);
                    $this->assertSame([
                        'file' => 'assets/import.versioned.js',
                    ], $chunk);
                },
                'resources/css/app.css' => function () use ($url, $chunk, $buildDir) {
                    $this->assertSame("https://example.com/{$buildDir}/assets/app.versioned.css", $url);
                    $this->assertSame([
                        'src' => 'resources/css/app.css',
                        'file' => 'assets/app.versioned.css',
                    ], $chunk);
                },
            })();

            return [
                'crossorigin',
                'data-persistent-across-pages' => 'YES',
                'remove-me' => false,
                'keep-me' => true,
                'null' => null,
                'empty-string' => '',
                'zero' => 0,
            ];
        });

        $result = app(Vite::class)(['resources/js/app.js'], $buildDir);

        $this->assertSame(
            '<link rel="preload" as="style" href="https://example.com/'.$buildDir.'/assets/app.versioned.css" general="attribute" crossorigin data-persistent-across-pages="YES" keep-me empty-string="" zero="0" />'
            .'<link rel="modulepreload" href="https://example.com/'.$buildDir.'/assets/app.versioned.js" general="attribute" crossorigin data-persistent-across-pages="YES" keep-me empty-string="" zero="0" />'
            .'<link rel="modulepreload" href="https://example.com/'.$buildDir.'/assets/import.versioned.js" general="attribute" crossorigin data-persistent-across-pages="YES" keep-me empty-string="" zero="0" />'
            .'<link rel="stylesheet" href="https://example.com/'.$buildDir.'/assets/app.versioned.css" />'
            .'<script type="module" src="https://example.com/'.$buildDir.'/assets/app.versioned.js"></script>',
            $result->toHtml());

        $this->assertSame([
            "https://example.com/$buildDir/assets/app.versioned.css" => [
                'rel="preload"',
                'as="style"',
                'general="attribute"',
                'crossorigin',
                'data-persistent-across-pages="YES"',
                'keep-me',
                'empty-string=""',
                'zero="0"',
            ],
            "https://example.com/$buildDir/assets/app.versioned.js" => [
                'rel="modulepreload"',
                'general="attribute"',
                'crossorigin',
                'data-persistent-across-pages="YES"',
                'keep-me',
                'empty-string=""',
                'zero="0"',
            ],
            "https://example.com/$buildDir/assets/import.versioned.js" => [
                'rel="modulepreload"',
                'general="attribute"',
                'crossorigin',
                'data-persistent-across-pages="YES"',
                'keep-me',
                'empty-string=""',
                'zero="0"',
            ],
        ], ViteFacade::preloadedAssets());

        $this->cleanViteManifest($buildDir);
    }

    public function testItCanSuppressPreloadTagGeneration()
    {
        $buildDir = Str::random();
        $this->makeViteManifest([
            'resources/js/app.js' => [
                'src' => 'resources/js/app.js',
                'file' => 'assets/app.versioned.js',
                'imports' => [
                    'import.js',
                    'import-nopreload.js',
                ],
                'css' => [
                    'assets/app.versioned.css',
                    'assets/app-nopreload.versioned.css',
                ],
            ],
            'resources/js/app-nopreload.js' => [
                'src' => 'resources/js/app-nopreload.js',
                'file' => 'assets/app-nopreload.versioned.js',
            ],
            'import.js' => [
                'file' => 'assets/import.versioned.js',
            ],
            'import-nopreload.js' => [
                'file' => 'assets/import-nopreload.versioned.js',
            ],
            'resources/css/app.css' => [
                'src' => 'resources/css/app.css',
                'file' => 'assets/app.versioned.css',
            ],
            'resources/css/app-nopreload.css' => [
                'src' => 'resources/css/app-nopreload.css',
                'file' => 'assets/app-nopreload.versioned.css',
            ],
        ], $buildDir);
        ViteFacade::usePreloadTagAttributes(function ($src, $url, $chunk, $manifest) use ($buildDir) {
            $this->assertSame([
                'resources/js/app.js' => [
                    'src' => 'resources/js/app.js',
                    'file' => 'assets/app.versioned.js',
                    'imports' => [
                        'import.js',
                        'import-nopreload.js',
                    ],
                    'css' => [
                        'assets/app.versioned.css',
                        'assets/app-nopreload.versioned.css',
                    ],
                ],
                'resources/js/app-nopreload.js' => [
                    'src' => 'resources/js/app-nopreload.js',
                    'file' => 'assets/app-nopreload.versioned.js',
                ],
                'import.js' => [
                    'file' => 'assets/import.versioned.js',
                ],
                'import-nopreload.js' => [
                    'file' => 'assets/import-nopreload.versioned.js',
                ],
                'resources/css/app.css' => [
                    'src' => 'resources/css/app.css',
                    'file' => 'assets/app.versioned.css',
                ],
                'resources/css/app-nopreload.css' => [
                    'src' => 'resources/css/app-nopreload.css',
                    'file' => 'assets/app-nopreload.versioned.css',
                ],
            ], $manifest);

            (match ($src) {
                'resources/js/app.js' => function () use ($url, $chunk, $buildDir) {
                    $this->assertSame("https://example.com/{$buildDir}/assets/app.versioned.js", $url);
                    $this->assertSame([
                        'src' => 'resources/js/app.js',
                        'file' => 'assets/app.versioned.js',
                        'imports' => [
                            'import.js',
                            'import-nopreload.js',
                        ],
                        'css' => [
                            'assets/app.versioned.css',
                            'assets/app-nopreload.versioned.css',
                        ],
                    ], $chunk);
                },
                'resources/js/app-nopreload.js' => function () use ($url, $chunk, $buildDir) {
                    $this->assertSame("https://example.com/{$buildDir}/assets/app-nopreload.versioned.js", $url);
                    $this->assertSame([
                        'src' => 'resources/js/app-nopreload.js',
                        'file' => 'assets/app-nopreload.versioned.js',
                    ], $chunk);
                },
                'import.js' => function () use ($url, $chunk, $buildDir) {
                    $this->assertSame("https://example.com/{$buildDir}/assets/import.versioned.js", $url);
                    $this->assertSame([
                        'file' => 'assets/import.versioned.js',
                    ], $chunk);
                },
                'import-nopreload.js' => function () use ($url, $chunk, $buildDir) {
                    $this->assertSame("https://example.com/{$buildDir}/assets/import-nopreload.versioned.js", $url);
                    $this->assertSame([
                        'file' => 'assets/import-nopreload.versioned.js',
                    ], $chunk);
                },
                'resources/css/app.css' => function () use ($url, $chunk, $buildDir) {
                    $this->assertSame("https://example.com/{$buildDir}/assets/app.versioned.css", $url);
                    $this->assertSame([
                        'src' => 'resources/css/app.css',
                        'file' => 'assets/app.versioned.css',
                    ], $chunk);
                },
                'resources/css/app-nopreload.css' => function () use ($url, $chunk, $buildDir) {
                    $this->assertSame("https://example.com/{$buildDir}/assets/app-nopreload.versioned.css", $url);
                    $this->assertSame([
                        'src' => 'resources/css/app-nopreload.css',
                        'file' => 'assets/app-nopreload.versioned.css',
                    ], $chunk);
                },
            })();

            return Str::contains($src, '-nopreload') ? false : [];
        });

        $result = app(Vite::class)(['resources/js/app.js', 'resources/js/app-nopreload.js'], $buildDir);

        $this->assertSame(
            '<link rel="preload" as="style" href="https://example.com/'.$buildDir.'/assets/app.versioned.css" />'
            .'<link rel="modulepreload" href="https://example.com/'.$buildDir.'/assets/app.versioned.js" />'
            .'<link rel="modulepreload" href="https://example.com/'.$buildDir.'/assets/import.versioned.js" />'
            .'<link rel="stylesheet" href="https://example.com/'.$buildDir.'/assets/app.versioned.css" />'
            .'<link rel="stylesheet" href="https://example.com/'.$buildDir.'/assets/app-nopreload.versioned.css" />'
            .'<script type="module" src="https://example.com/'.$buildDir.'/assets/app.versioned.js"></script>'
            .'<script type="module" src="https://example.com/'.$buildDir.'/assets/app-nopreload.versioned.js"></script>',
            $result->toHtml());

        $this->assertSame([
            "https://example.com/$buildDir/assets/app.versioned.css" => [
                'rel="preload"',
                'as="style"',
            ],
            "https://example.com/$buildDir/assets/app.versioned.js" => [
                'rel="modulepreload"',
            ],
            "https://example.com/$buildDir/assets/import.versioned.js" => [
                'rel="modulepreload"',
            ],
        ], ViteFacade::preloadedAssets());

        $this->cleanViteManifest($buildDir);
    }

    public function testPreloadAssetsGetAssetNonce()
    {
        $buildDir = Str::random();
        $this->makeViteManifest([
            'resources/js/app.js' => [
                'src' => 'resources/js/app.js',
                'file' => 'assets/app.versioned.js',
                'css' => [
                    'assets/app.versioned.css',
                ],
            ],
            'resources/css/app.css' => [
                'src' => 'resources/css/app.css',
                'file' => 'assets/app.versioned.css',
            ],
        ], $buildDir);
        ViteFacade::useCspNonce('expected-nonce');

        $result = app(Vite::class)(['resources/js/app.js'], $buildDir);

        $this->assertSame(
            '<link rel="preload" as="style" href="https://example.com/'.$buildDir.'/assets/app.versioned.css" nonce="expected-nonce" />'
            .'<link rel="modulepreload" href="https://example.com/'.$buildDir.'/assets/app.versioned.js" nonce="expected-nonce" />'
            .'<link rel="stylesheet" href="https://example.com/'.$buildDir.'/assets/app.versioned.css" nonce="expected-nonce" />'
            .'<script type="module" src="https://example.com/'.$buildDir.'/assets/app.versioned.js" nonce="expected-nonce"></script>',
            $result->toHtml());

        $this->assertSame([
            "https://example.com/$buildDir/assets/app.versioned.css" => [
                'rel="preload"',
                'as="style"',
                'nonce="expected-nonce"',
            ],
            "https://example.com/$buildDir/assets/app.versioned.js" => [
                'rel="modulepreload"',
                'nonce="expected-nonce"',
            ],
        ], ViteFacade::preloadedAssets());

        $this->cleanViteManifest($buildDir);
    }

    public function testCrossoriginAttributeIsInheritedByPreloadTags()
    {
        $buildDir = Str::random();
        $this->makeViteManifest([
            'resources/js/app.js' => [
                'src' => 'resources/js/app.js',
                'file' => 'assets/app.versioned.js',
                'css' => [
                    'assets/app.versioned.css',
                ],
            ],
            'resources/css/app.css' => [
                'src' => 'resources/css/app.css',
                'file' => 'assets/app.versioned.css',
            ],
        ], $buildDir);
        ViteFacade::useScriptTagAttributes([
            'crossorigin' => 'script-crossorigin',
        ]);
        ViteFacade::useStyleTagAttributes([
            'crossorigin' => 'style-crossorigin',
        ]);

        $result = app(Vite::class)(['resources/js/app.js'], $buildDir);

        $this->assertSame(
            '<link rel="preload" as="style" href="https://example.com/'.$buildDir.'/assets/app.versioned.css" crossorigin="style-crossorigin" />'
            .'<link rel="modulepreload" href="https://example.com/'.$buildDir.'/assets/app.versioned.js" crossorigin="script-crossorigin" />'
            .'<link rel="stylesheet" href="https://example.com/'.$buildDir.'/assets/app.versioned.css" crossorigin="style-crossorigin" />'
            .'<script type="module" src="https://example.com/'.$buildDir.'/assets/app.versioned.js" crossorigin="script-crossorigin"></script>',
            $result->toHtml());

        $this->assertSame([
            "https://example.com/$buildDir/assets/app.versioned.css" => [
                'rel="preload"',
                'as="style"',
                'crossorigin="style-crossorigin"',
            ],
            "https://example.com/$buildDir/assets/app.versioned.js" => [
                'rel="modulepreload"',
                'crossorigin="script-crossorigin"',
            ],
        ], ViteFacade::preloadedAssets());

        $this->cleanViteManifest($buildDir);
    }

    public function testItCanConfigureTheManifestFilename()
    {
        $buildDir = Str::random();
        app()->usePublicPath(__DIR__);
        if (! file_exists(public_path($buildDir))) {
            mkdir(public_path($buildDir));
        }
        $contents = json_encode([
            'resources/js/app.js' => [
                'src' => 'resources/js/app-from-custom-manifest.js',
                'file' => 'assets/app-from-custom-manifest.versioned.js',
            ],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        file_put_contents(public_path("{$buildDir}/custom-manifest.json"), $contents);

        ViteFacade::useManifestFilename('custom-manifest.json');

        $result = app(Vite::class)(['resources/js/app.js'], $buildDir);

        $this->assertSame(
            '<link rel="modulepreload" href="https://example.com/'.$buildDir.'/assets/app-from-custom-manifest.versioned.js" />'
            .'<script type="module" src="https://example.com/'.$buildDir.'/assets/app-from-custom-manifest.versioned.js"></script>',
            $result->toHtml());

        unlink(public_path("{$buildDir}/custom-manifest.json"));
        rmdir(public_path($buildDir));
    }

    public function testItOnlyOutputsUniquePreloadTags()
    {
        $buildDir = Str::random();
        $this->makeViteManifest([
            'resources/js/app.css' => [
                'file' => 'assets/app-versioned.css',
                'src' => 'resources/js/app.css',
            ],
            'resources/js/Pages/Welcome.vue' => [
                'file' => 'assets/Welcome-versioned.js',
                'src' => 'resources/js/Pages/Welcome.vue',
                'imports' => [
                    'resources/js/app.js',
                ],
            ],
            'resources/js/app.js' => [
                'file' => 'assets/app-versioned.js',
                'src' => 'resources/js/app.js',
                'css' => [
                    'assets/app-versioned.css',
                ],
            ],
        ], $buildDir);

        $result = app(Vite::class)(['resources/js/app.js', 'resources/js/Pages/Welcome.vue'], $buildDir);

        $this->assertSame(
            '<link rel="preload" as="style" href="https://example.com/'.$buildDir.'/assets/app-versioned.css" />'
            .'<link rel="modulepreload" href="https://example.com/'.$buildDir.'/assets/app-versioned.js" />'
            .'<link rel="modulepreload" href="https://example.com/'.$buildDir.'/assets/Welcome-versioned.js" />'
            .'<link rel="stylesheet" href="https://example.com/'.$buildDir.'/assets/app-versioned.css" />'
            .'<script type="module" src="https://example.com/'.$buildDir.'/assets/app-versioned.js"></script>'
            .'<script type="module" src="https://example.com/'.$buildDir.'/assets/Welcome-versioned.js"></script>',
            $result->toHtml());

        $this->assertSame([
            "https://example.com/$buildDir/assets/app-versioned.css" => [
                'rel="preload"',
                'as="style"',
            ],
            "https://example.com/$buildDir/assets/app-versioned.js" => [
                'rel="modulepreload"',
            ],
            "https://example.com/$buildDir/assets/Welcome-versioned.js" => [
                'rel="modulepreload"',
            ],
        ], ViteFacade::preloadedAssets());

        $this->cleanViteManifest($buildDir);
    }

    public function testItRetrievesAssetContent()
    {
        $this->makeViteManifest();

        $this->makeAsset('/app.versioned.js', 'some content');

        $content = ViteFacade::content('resources/js/app.js');

        $this->assertSame('some content', $content);

        $this->cleanAsset('/app.versioned.js');

        $this->cleanViteManifest();
    }

    public function testItThrowsWhenUnableToFindFileToRetrieveContent()
    {
        $this->makeViteManifest();

        $this->expectException(ViteException::class);
        $this->expectExceptionMessage('Unable to locate file from Vite manifest: '.public_path('build/assets/app.versioned.js'));

        ViteFacade::content('resources/js/app.js');
    }

    protected function makeViteManifest($contents = null, $path = 'build')
    {
        app()->usePublicPath(__DIR__);

        if (! file_exists(public_path($path))) {
            mkdir(public_path($path));
        }

        $manifest = json_encode($contents ?? [
            'resources/js/app.js' => [
                'src' => 'resources/js/app.js',
                'file' => 'assets/app.versioned.js',
            ],
            'resources/js/app-with-css-import.js' => [
                'src' => 'resources/js/app-with-css-import.js',
                'file' => 'assets/app-with-css-import.versioned.js',
                'css' => [
                    'assets/imported-css.versioned.css',
                ],
            ],
            'resources/css/imported-css.css' => [
                // 'src' => 'resources/css/imported-css.css',
                'file' => 'assets/imported-css.versioned.css',
            ],
            'resources/js/app-with-shared-css.js' => [
                'src' => 'resources/js/app-with-shared-css.js',
                'file' => 'assets/app-with-shared-css.versioned.js',
                'imports' => [
                    '_someFile.js',
                ],
            ],
            'resources/css/app.css' => [
                'src' => 'resources/css/app.css',
                'file' => 'assets/app.versioned.css',
            ],
            '_someFile.js' => [
                'file' => 'assets/someFile.versioned.js',
                'css' => [
                    'assets/shared-css.versioned.css',
                ],
            ],
            'resources/css/shared-css' => [
                'src' => 'resources/css/shared-css',
                'file' => 'assets/shared-css.versioned.css',
            ],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        file_put_contents(public_path("{$path}/manifest.json"), $manifest);
    }

    public function testItCanPrefetchEntrypoint()
    {
        $manifest = json_decode(file_get_contents(__DIR__.'/fixtures/prefetching-manifest.json'));
        $buildDir = Str::random();
        $this->makeViteManifest($manifest, $buildDir);
        app()->usePublicPath(__DIR__);

        $html = (string) ViteFacade::withEntryPoints(['resources/js/app.js'])->useBuildDirectory($buildDir)->prefetch(concurrency: 3)->toHtml();

        $expectedAssets = Js::from([
            ['rel' => 'prefetch', 'href' => "https://example.com/{$buildDir}/assets/ConfirmPassword-CDwcgU8E.js", 'fetchpriority' => 'low'],
            ['rel' => 'prefetch', 'href' => "https://example.com/{$buildDir}/assets/GuestLayout-BY3LC-73.js", 'fetchpriority' => 'low'],
            ['rel' => 'prefetch', 'href' => "https://example.com/{$buildDir}/assets/TextInput-C8CCB_U_.js", 'fetchpriority' => 'low'],
            ['rel' => 'prefetch', 'href' => "https://example.com/{$buildDir}/assets/PrimaryButton-DuXwr-9M.js", 'fetchpriority' => 'low'],
            ['rel' => 'prefetch', 'href' => "https://example.com/{$buildDir}/assets/ApplicationLogo-BhIZH06z.js", 'fetchpriority' => 'low'],
            ['rel' => 'prefetch', 'href' => "https://example.com/{$buildDir}/assets/_plugin-vue_export-helper-DlAUqK2U.js", 'fetchpriority' => 'low'],
            ['rel' => 'prefetch', 'href' => "https://example.com/{$buildDir}/assets/ForgotPassword-B0WWE0BO.js", 'fetchpriority' => 'low'],
            ['rel' => 'prefetch', 'href' => "https://example.com/{$buildDir}/assets/Login-DAFSdGSW.js", 'fetchpriority' => 'low'],
            ['rel' => 'prefetch', 'href' => "https://example.com/{$buildDir}/assets/Register-CfYQbTlA.js", 'fetchpriority' => 'low'],
            ['rel' => 'prefetch', 'href' => "https://example.com/{$buildDir}/assets/ResetPassword-BNl7a4X1.js", 'fetchpriority' => 'low'],
            ['rel' => 'prefetch', 'href' => "https://example.com/{$buildDir}/assets/VerifyEmail-CyukB_SZ.js", 'fetchpriority' => 'low'],
            ['rel' => 'prefetch', 'href' => "https://example.com/{$buildDir}/assets/Dashboard-DM_LxQy2.js", 'fetchpriority' => 'low'],
            ['rel' => 'prefetch', 'href' => "https://example.com/{$buildDir}/assets/AuthenticatedLayout-DfWF52N1.js", 'fetchpriority' => 'low'],
            ['rel' => 'prefetch', 'href' => "https://example.com/{$buildDir}/assets/Edit-CYV2sXpe.js", 'fetchpriority' => 'low'],
            ['rel' => 'prefetch', 'href' => "https://example.com/{$buildDir}/assets/DeleteUserForm-B1oHFaVP.js", 'fetchpriority' => 'low'],
            ['rel' => 'prefetch', 'href' => "https://example.com/{$buildDir}/assets/UpdatePasswordForm-CaeWqGla.js", 'fetchpriority' => 'low'],
            ['rel' => 'prefetch', 'href' => "https://example.com/{$buildDir}/assets/UpdateProfileInformationForm-CJwkYwQQ.js", 'fetchpriority' => 'low'],
            ['rel' => 'prefetch', 'href' => "https://example.com/{$buildDir}/assets/Welcome-D_7l79PQ.js", 'fetchpriority' => 'low'],
        ]);
        $this->assertSame(<<<HTML
        <link rel="preload" as="style" href="https://example.com/{$buildDir}/assets/index-B3s1tYeC.css" /><link rel="modulepreload" href="https://example.com/{$buildDir}/assets/app-lliD09ip.js" /><link rel="modulepreload" href="https://example.com/{$buildDir}/assets/index-BSdK3M0e.js" /><link rel="stylesheet" href="https://example.com/{$buildDir}/assets/index-B3s1tYeC.css" /><script type="module" src="https://example.com/{$buildDir}/assets/app-lliD09ip.js"></script>
        <script>
             window.addEventListener('load', () => window.setTimeout(() => {
                const makeLink = (asset) => {
                    const link = document.createElement('link')

                    Object.keys(asset).forEach((attribute) => {
                        link.setAttribute(attribute, asset[attribute])
                    })

                    return link
                }

                const loadNext = (assets, count) => window.setTimeout(() => {
                    if (count > assets.length) {
                        count = assets.length

                        if (count === 0) {
                            return
                        }
                    }

                    const fragment = new DocumentFragment

                    while (count > 0) {
                        const link = makeLink(assets.shift())
                        fragment.append(link)
                        count--

                        if (assets.length) {
                            link.onload = () => loadNext(assets, 1)
                            link.onerror = () => loadNext(assets, 1)
                        }
                    }

                    document.head.append(fragment)
                })

                loadNext({$expectedAssets}, 3)
            }))
        </script>
        HTML, $html);

        $this->cleanViteManifest($buildDir);
    }

    public function testItHandlesSpecifyingPageWithAppJs()
    {
        $manifest = json_decode(file_get_contents(__DIR__.'/fixtures/prefetching-manifest.json'));
        $buildDir = Str::random();
        $this->makeViteManifest($manifest, $buildDir);
        app()->usePublicPath(__DIR__);

        $html = (string) ViteFacade::withEntryPoints(['resources/js/app.js', 'resources/js/Pages/Auth/Login.vue'])->useBuildDirectory($buildDir)->prefetch(concurrency: 3)->toHtml();

        $expectedAssets = Js::from([
            ['rel' => 'prefetch', 'href' => "https://example.com/{$buildDir}/assets/ConfirmPassword-CDwcgU8E.js", 'fetchpriority' => 'low'],
            ['rel' => 'prefetch', 'href' => "https://example.com/{$buildDir}/assets/ForgotPassword-B0WWE0BO.js", 'fetchpriority' => 'low'],
            ['rel' => 'prefetch', 'href' => "https://example.com/{$buildDir}/assets/Register-CfYQbTlA.js", 'fetchpriority' => 'low'],
            ['rel' => 'prefetch', 'href' => "https://example.com/{$buildDir}/assets/ResetPassword-BNl7a4X1.js", 'fetchpriority' => 'low'],
            ['rel' => 'prefetch', 'href' => "https://example.com/{$buildDir}/assets/VerifyEmail-CyukB_SZ.js", 'fetchpriority' => 'low'],
            ['rel' => 'prefetch', 'href' => "https://example.com/{$buildDir}/assets/Dashboard-DM_LxQy2.js", 'fetchpriority' => 'low'],
            ['rel' => 'prefetch', 'href' => "https://example.com/{$buildDir}/assets/AuthenticatedLayout-DfWF52N1.js", 'fetchpriority' => 'low'],
            ['rel' => 'prefetch', 'href' => "https://example.com/{$buildDir}/assets/Edit-CYV2sXpe.js", 'fetchpriority' => 'low'],
            ['rel' => 'prefetch', 'href' => "https://example.com/{$buildDir}/assets/DeleteUserForm-B1oHFaVP.js", 'fetchpriority' => 'low'],
            ['rel' => 'prefetch', 'href' => "https://example.com/{$buildDir}/assets/UpdatePasswordForm-CaeWqGla.js", 'fetchpriority' => 'low'],
            ['rel' => 'prefetch', 'href' => "https://example.com/{$buildDir}/assets/UpdateProfileInformationForm-CJwkYwQQ.js", 'fetchpriority' => 'low'],
            ['rel' => 'prefetch', 'href' => "https://example.com/{$buildDir}/assets/Welcome-D_7l79PQ.js", 'fetchpriority' => 'low'],
        ]);
        $this->assertStringContainsString(<<<JAVASCRIPT
                loadNext({$expectedAssets}, 3)
            JAVASCRIPT, $html);

        $this->cleanViteManifest($buildDir);
    }

    public function testItCanSpecifyWaterfallChunks()
    {
        $manifest = json_decode(file_get_contents(__DIR__.'/fixtures/prefetching-manifest.json'));
        $buildDir = Str::random();
        $this->makeViteManifest($manifest, $buildDir);
        app()->usePublicPath(__DIR__);

        $html = (string) ViteFacade::withEntryPoints(['resources/js/app.js'])->useBuildDirectory($buildDir)->prefetch(concurrency: 10)->toHtml();

        $expectedAssets = Js::from([
            ['rel' => 'prefetch', 'href' => "https://example.com/{$buildDir}/assets/ConfirmPassword-CDwcgU8E.js", 'fetchpriority' => 'low'],
            ['rel' => 'prefetch', 'href' => "https://example.com/{$buildDir}/assets/GuestLayout-BY3LC-73.js", 'fetchpriority' => 'low'],
            ['rel' => 'prefetch', 'href' => "https://example.com/{$buildDir}/assets/TextInput-C8CCB_U_.js", 'fetchpriority' => 'low'],
            ['rel' => 'prefetch', 'href' => "https://example.com/{$buildDir}/assets/PrimaryButton-DuXwr-9M.js", 'fetchpriority' => 'low'],
            ['rel' => 'prefetch', 'href' => "https://example.com/{$buildDir}/assets/ApplicationLogo-BhIZH06z.js", 'fetchpriority' => 'low'],
            ['rel' => 'prefetch', 'href' => "https://example.com/{$buildDir}/assets/_plugin-vue_export-helper-DlAUqK2U.js", 'fetchpriority' => 'low'],
            ['rel' => 'prefetch', 'href' => "https://example.com/{$buildDir}/assets/ForgotPassword-B0WWE0BO.js", 'fetchpriority' => 'low'],
            ['rel' => 'prefetch', 'href' => "https://example.com/{$buildDir}/assets/Login-DAFSdGSW.js", 'fetchpriority' => 'low'],
            ['rel' => 'prefetch', 'href' => "https://example.com/{$buildDir}/assets/Register-CfYQbTlA.js", 'fetchpriority' => 'low'],
            ['rel' => 'prefetch', 'href' => "https://example.com/{$buildDir}/assets/ResetPassword-BNl7a4X1.js", 'fetchpriority' => 'low'],
            ['rel' => 'prefetch', 'href' => "https://example.com/{$buildDir}/assets/VerifyEmail-CyukB_SZ.js", 'fetchpriority' => 'low'],
            ['rel' => 'prefetch', 'href' => "https://example.com/{$buildDir}/assets/Dashboard-DM_LxQy2.js", 'fetchpriority' => 'low'],
            ['rel' => 'prefetch', 'href' => "https://example.com/{$buildDir}/assets/AuthenticatedLayout-DfWF52N1.js", 'fetchpriority' => 'low'],
            ['rel' => 'prefetch', 'href' => "https://example.com/{$buildDir}/assets/Edit-CYV2sXpe.js", 'fetchpriority' => 'low'],
            ['rel' => 'prefetch', 'href' => "https://example.com/{$buildDir}/assets/DeleteUserForm-B1oHFaVP.js", 'fetchpriority' => 'low'],
            ['rel' => 'prefetch', 'href' => "https://example.com/{$buildDir}/assets/UpdatePasswordForm-CaeWqGla.js", 'fetchpriority' => 'low'],
            ['rel' => 'prefetch', 'href' => "https://example.com/{$buildDir}/assets/UpdateProfileInformationForm-CJwkYwQQ.js", 'fetchpriority' => 'low'],
            ['rel' => 'prefetch', 'href' => "https://example.com/{$buildDir}/assets/Welcome-D_7l79PQ.js", 'fetchpriority' => 'low'],
        ]);
        $this->assertStringContainsString(<<<JAVASCRIPT
                loadNext({$expectedAssets}, 10)
            JAVASCRIPT, $html);

        $this->cleanViteManifest($buildDir);
    }

    public function testItCanPrefetchAggressively()
    {
        $manifest = json_decode(file_get_contents(__DIR__.'/fixtures/prefetching-manifest.json'));
        $buildDir = Str::random();
        $this->makeViteManifest($manifest, $buildDir);
        app()->usePublicPath(__DIR__);

        $html = (string) ViteFacade::withEntryPoints(['resources/js/app.js'])->useBuildDirectory($buildDir)->prefetch()->toHtml();

        $expectedAssets = Js::from([
            ['rel' => 'prefetch', 'href' => "https://example.com/{$buildDir}/assets/ConfirmPassword-CDwcgU8E.js", 'fetchpriority' => 'low'],
            ['rel' => 'prefetch', 'href' => "https://example.com/{$buildDir}/assets/GuestLayout-BY3LC-73.js", 'fetchpriority' => 'low'],
            ['rel' => 'prefetch', 'href' => "https://example.com/{$buildDir}/assets/TextInput-C8CCB_U_.js", 'fetchpriority' => 'low'],
            ['rel' => 'prefetch', 'href' => "https://example.com/{$buildDir}/assets/PrimaryButton-DuXwr-9M.js", 'fetchpriority' => 'low'],
            ['rel' => 'prefetch', 'href' => "https://example.com/{$buildDir}/assets/ApplicationLogo-BhIZH06z.js", 'fetchpriority' => 'low'],
            ['rel' => 'prefetch', 'href' => "https://example.com/{$buildDir}/assets/_plugin-vue_export-helper-DlAUqK2U.js", 'fetchpriority' => 'low'],
            ['rel' => 'prefetch', 'href' => "https://example.com/{$buildDir}/assets/ForgotPassword-B0WWE0BO.js", 'fetchpriority' => 'low'],
            ['rel' => 'prefetch', 'href' => "https://example.com/{$buildDir}/assets/Login-DAFSdGSW.js", 'fetchpriority' => 'low'],
            ['rel' => 'prefetch', 'href' => "https://example.com/{$buildDir}/assets/Register-CfYQbTlA.js", 'fetchpriority' => 'low'],
            ['rel' => 'prefetch', 'href' => "https://example.com/{$buildDir}/assets/ResetPassword-BNl7a4X1.js", 'fetchpriority' => 'low'],
            ['rel' => 'prefetch', 'href' => "https://example.com/{$buildDir}/assets/VerifyEmail-CyukB_SZ.js", 'fetchpriority' => 'low'],
            ['rel' => 'prefetch', 'href' => "https://example.com/{$buildDir}/assets/Dashboard-DM_LxQy2.js", 'fetchpriority' => 'low'],
            ['rel' => 'prefetch', 'href' => "https://example.com/{$buildDir}/assets/AuthenticatedLayout-DfWF52N1.js", 'fetchpriority' => 'low'],
            ['rel' => 'prefetch', 'href' => "https://example.com/{$buildDir}/assets/Edit-CYV2sXpe.js", 'fetchpriority' => 'low'],
            ['rel' => 'prefetch', 'href' => "https://example.com/{$buildDir}/assets/DeleteUserForm-B1oHFaVP.js", 'fetchpriority' => 'low'],
            ['rel' => 'prefetch', 'href' => "https://example.com/{$buildDir}/assets/UpdatePasswordForm-CaeWqGla.js", 'fetchpriority' => 'low'],
            ['rel' => 'prefetch', 'href' => "https://example.com/{$buildDir}/assets/UpdateProfileInformationForm-CJwkYwQQ.js", 'fetchpriority' => 'low'],
            ['rel' => 'prefetch', 'href' => "https://example.com/{$buildDir}/assets/Welcome-D_7l79PQ.js", 'fetchpriority' => 'low'],
        ]);

        $this->assertSame(<<<HTML
        <link rel="preload" as="style" href="https://example.com/{$buildDir}/assets/index-B3s1tYeC.css" /><link rel="modulepreload" href="https://example.com/{$buildDir}/assets/app-lliD09ip.js" /><link rel="modulepreload" href="https://example.com/{$buildDir}/assets/index-BSdK3M0e.js" /><link rel="stylesheet" href="https://example.com/{$buildDir}/assets/index-B3s1tYeC.css" /><script type="module" src="https://example.com/{$buildDir}/assets/app-lliD09ip.js"></script>
        <script>
             window.addEventListener('load', () => window.setTimeout(() => {
                const makeLink = (asset) => {
                    const link = document.createElement('link')

                    Object.keys(asset).forEach((attribute) => {
                        link.setAttribute(attribute, asset[attribute])
                    })

                    return link
                }

                const fragment = new DocumentFragment;
                {$expectedAssets}.forEach((asset) => fragment.append(makeLink(asset)))
                document.head.append(fragment)
             }))
        </script>
        HTML, $html);

        $this->cleanViteManifest($buildDir);
    }

    public function testAddsAttributesToPrefetchTags()
    {
        $manifest = json_decode(file_get_contents(__DIR__.'/fixtures/prefetching-manifest.json'));
        $buildDir = Str::random();
        $this->makeViteManifest($manifest, $buildDir);
        app()->usePublicPath(__DIR__);

        $html = (string) tap(ViteFacade::withEntryPoints(['resources/js/app.js'])->useBuildDirectory($buildDir)->prefetch(concurrency: 3))->useCspNonce('abc123')->toHtml();

        $expectedAssets = Js::from([
            ['rel' => 'prefetch', 'href' => "https://example.com/{$buildDir}/assets/ConfirmPassword-CDwcgU8E.js", 'nonce' => 'abc123', 'fetchpriority' => 'low'],
            ['rel' => 'prefetch', 'href' => "https://example.com/{$buildDir}/assets/GuestLayout-BY3LC-73.js", 'nonce' => 'abc123', 'fetchpriority' => 'low'],
            ['rel' => 'prefetch', 'href' => "https://example.com/{$buildDir}/assets/TextInput-C8CCB_U_.js", 'nonce' => 'abc123', 'fetchpriority' => 'low'],
            ['rel' => 'prefetch', 'href' => "https://example.com/{$buildDir}/assets/PrimaryButton-DuXwr-9M.js", 'nonce' => 'abc123', 'fetchpriority' => 'low'],
            ['rel' => 'prefetch', 'href' => "https://example.com/{$buildDir}/assets/ApplicationLogo-BhIZH06z.js", 'nonce' => 'abc123', 'fetchpriority' => 'low'],
            ['rel' => 'prefetch', 'href' => "https://example.com/{$buildDir}/assets/_plugin-vue_export-helper-DlAUqK2U.js", 'nonce' => 'abc123', 'fetchpriority' => 'low'],
            ['rel' => 'prefetch', 'href' => "https://example.com/{$buildDir}/assets/ForgotPassword-B0WWE0BO.js", 'nonce' => 'abc123', 'fetchpriority' => 'low'],
            ['rel' => 'prefetch', 'href' => "https://example.com/{$buildDir}/assets/Login-DAFSdGSW.js", 'nonce' => 'abc123', 'fetchpriority' => 'low'],
            ['rel' => 'prefetch', 'href' => "https://example.com/{$buildDir}/assets/Register-CfYQbTlA.js", 'nonce' => 'abc123', 'fetchpriority' => 'low'],
            ['rel' => 'prefetch', 'href' => "https://example.com/{$buildDir}/assets/ResetPassword-BNl7a4X1.js", 'nonce' => 'abc123', 'fetchpriority' => 'low'],
            ['rel' => 'prefetch', 'href' => "https://example.com/{$buildDir}/assets/VerifyEmail-CyukB_SZ.js", 'nonce' => 'abc123', 'fetchpriority' => 'low'],
            ['rel' => 'prefetch', 'href' => "https://example.com/{$buildDir}/assets/Dashboard-DM_LxQy2.js", 'nonce' => 'abc123', 'fetchpriority' => 'low'],
            ['rel' => 'prefetch', 'href' => "https://example.com/{$buildDir}/assets/AuthenticatedLayout-DfWF52N1.js", 'nonce' => 'abc123', 'fetchpriority' => 'low'],
            ['rel' => 'prefetch', 'href' => "https://example.com/{$buildDir}/assets/Edit-CYV2sXpe.js", 'nonce' => 'abc123', 'fetchpriority' => 'low'],
            ['rel' => 'prefetch', 'href' => "https://example.com/{$buildDir}/assets/DeleteUserForm-B1oHFaVP.js", 'nonce' => 'abc123', 'fetchpriority' => 'low'],
            ['rel' => 'prefetch', 'href' => "https://example.com/{$buildDir}/assets/UpdatePasswordForm-CaeWqGla.js", 'nonce' => 'abc123', 'fetchpriority' => 'low'],
            ['rel' => 'prefetch', 'href' => "https://example.com/{$buildDir}/assets/UpdateProfileInformationForm-CJwkYwQQ.js", 'nonce' => 'abc123', 'fetchpriority' => 'low'],
            ['rel' => 'prefetch', 'href' => "https://example.com/{$buildDir}/assets/Welcome-D_7l79PQ.js", 'nonce' => 'abc123', 'fetchpriority' => 'low'],
        ]);
        $this->assertStringContainsString(<<<JAVASCRIPT
                loadNext({$expectedAssets}, 3)
        JAVASCRIPT, $html);

        $this->cleanViteManifest($buildDir);
    }

    public function testItNormalisesAttributes()
    {
        $manifest = json_decode(file_get_contents(__DIR__.'/fixtures/prefetching-manifest.json'));
        $buildDir = Str::random();
        $this->makeViteManifest($manifest, $buildDir);
        app()->usePublicPath(__DIR__);

        $html = (string) tap(ViteFacade::withEntryPoints(['resources/js/app.js']))->useBuildDirectory($buildDir)->prefetch(concurrency: 3)->usePreloadTagAttributes([
            'key' => 'value',
            'key-only',
            'true-value' => true,
            'false-value' => false,
            'null-value' => null,
        ])->toHtml();

        $expectedAssets = Js::from([
            ['rel' => 'prefetch', 'href' => "https://example.com/{$buildDir}/assets/ConfirmPassword-CDwcgU8E.js", 'key' => 'value', 'key-only' => 'key-only', 'true-value' => 'true-value', 'fetchpriority' => 'low'],
            ['rel' => 'prefetch', 'href' => "https://example.com/{$buildDir}/assets/GuestLayout-BY3LC-73.js", 'key' => 'value', 'key-only' => 'key-only', 'true-value' => 'true-value', 'fetchpriority' => 'low'],
            ['rel' => 'prefetch', 'href' => "https://example.com/{$buildDir}/assets/TextInput-C8CCB_U_.js", 'key' => 'value', 'key-only' => 'key-only', 'true-value' => 'true-value', 'fetchpriority' => 'low'],
            ['rel' => 'prefetch', 'href' => "https://example.com/{$buildDir}/assets/PrimaryButton-DuXwr-9M.js", 'key' => 'value', 'key-only' => 'key-only', 'true-value' => 'true-value', 'fetchpriority' => 'low'],
            ['rel' => 'prefetch', 'href' => "https://example.com/{$buildDir}/assets/ApplicationLogo-BhIZH06z.js", 'key' => 'value', 'key-only' => 'key-only', 'true-value' => 'true-value', 'fetchpriority' => 'low'],
            ['rel' => 'prefetch', 'href' => "https://example.com/{$buildDir}/assets/_plugin-vue_export-helper-DlAUqK2U.js", 'key' => 'value', 'key-only' => 'key-only', 'true-value' => 'true-value', 'fetchpriority' => 'low'],
            ['rel' => 'prefetch', 'href' => "https://example.com/{$buildDir}/assets/ForgotPassword-B0WWE0BO.js", 'key' => 'value', 'key-only' => 'key-only', 'true-value' => 'true-value', 'fetchpriority' => 'low'],
            ['rel' => 'prefetch', 'href' => "https://example.com/{$buildDir}/assets/Login-DAFSdGSW.js", 'key' => 'value', 'key-only' => 'key-only', 'true-value' => 'true-value', 'fetchpriority' => 'low'],
            ['rel' => 'prefetch', 'href' => "https://example.com/{$buildDir}/assets/Register-CfYQbTlA.js", 'key' => 'value', 'key-only' => 'key-only', 'true-value' => 'true-value', 'fetchpriority' => 'low'],
            ['rel' => 'prefetch', 'href' => "https://example.com/{$buildDir}/assets/ResetPassword-BNl7a4X1.js", 'key' => 'value', 'key-only' => 'key-only', 'true-value' => 'true-value', 'fetchpriority' => 'low'],
            ['rel' => 'prefetch', 'href' => "https://example.com/{$buildDir}/assets/VerifyEmail-CyukB_SZ.js", 'key' => 'value', 'key-only' => 'key-only', 'true-value' => 'true-value', 'fetchpriority' => 'low'],
            ['rel' => 'prefetch', 'href' => "https://example.com/{$buildDir}/assets/Dashboard-DM_LxQy2.js", 'key' => 'value', 'key-only' => 'key-only', 'true-value' => 'true-value', 'fetchpriority' => 'low'],
            ['rel' => 'prefetch', 'href' => "https://example.com/{$buildDir}/assets/AuthenticatedLayout-DfWF52N1.js", 'key' => 'value', 'key-only' => 'key-only', 'true-value' => 'true-value', 'fetchpriority' => 'low'],
            ['rel' => 'prefetch', 'href' => "https://example.com/{$buildDir}/assets/Edit-CYV2sXpe.js", 'key' => 'value', 'key-only' => 'key-only', 'true-value' => 'true-value', 'fetchpriority' => 'low'],
            ['rel' => 'prefetch', 'href' => "https://example.com/{$buildDir}/assets/DeleteUserForm-B1oHFaVP.js", 'key' => 'value', 'key-only' => 'key-only', 'true-value' => 'true-value', 'fetchpriority' => 'low'],
            ['rel' => 'prefetch', 'href' => "https://example.com/{$buildDir}/assets/UpdatePasswordForm-CaeWqGla.js", 'key' => 'value', 'key-only' => 'key-only', 'true-value' => 'true-value', 'fetchpriority' => 'low'],
            ['rel' => 'prefetch', 'href' => "https://example.com/{$buildDir}/assets/UpdateProfileInformationForm-CJwkYwQQ.js", 'key' => 'value', 'key-only' => 'key-only', 'true-value' => 'true-value', 'fetchpriority' => 'low'],
            ['rel' => 'prefetch', 'href' => "https://example.com/{$buildDir}/assets/Welcome-D_7l79PQ.js", 'key' => 'value', 'key-only' => 'key-only', 'true-value' => 'true-value', 'fetchpriority' => 'low'],
        ]);

        $this->assertStringContainsString(<<<JAVASCRIPT
                loadNext({$expectedAssets}, 3)
        JAVASCRIPT, $html);

        $this->cleanViteManifest($buildDir);
    }

    public function testItPrefetchesCss()
    {
        $manifest = json_decode(file_get_contents(__DIR__.'/fixtures/prefetching-manifest.json'));
        $buildDir = Str::random();
        $this->makeViteManifest($manifest, $buildDir);
        app()->usePublicPath(__DIR__);

        $html = (string) ViteFacade::withEntryPoints(['resources/js/admin.js'])->useBuildDirectory($buildDir)->prefetch(concurrency: 3)->toHtml();

        $expectedAssets = Js::from([
            ['rel' => 'prefetch', 'href' => "https://example.com/{$buildDir}/assets/ConfirmPassword-CDwcgU8E.js", 'fetchpriority' => 'low'],
            ['rel' => 'prefetch', 'href' => "https://example.com/{$buildDir}/assets/GuestLayout-BY3LC-73.js", 'fetchpriority' => 'low'],
            ['rel' => 'prefetch', 'href' => "https://example.com/{$buildDir}/assets/TextInput-C8CCB_U_.js", 'fetchpriority' => 'low'],
            ['rel' => 'prefetch', 'href' => "https://example.com/{$buildDir}/assets/PrimaryButton-DuXwr-9M.js", 'fetchpriority' => 'low'],
            ['rel' => 'prefetch', 'href' => "https://example.com/{$buildDir}/assets/ApplicationLogo-BhIZH06z.js", 'fetchpriority' => 'low'],
            ['rel' => 'prefetch', 'href' => "https://example.com/{$buildDir}/assets/_plugin-vue_export-helper-DlAUqK2U.js", 'fetchpriority' => 'low'],
            ['rel' => 'prefetch', 'href' => "https://example.com/{$buildDir}/assets/ForgotPassword-B0WWE0BO.js", 'fetchpriority' => 'low'],
            ['rel' => 'prefetch', 'href' => "https://example.com/{$buildDir}/assets/Login-DAFSdGSW.js", 'fetchpriority' => 'low'],
            ['rel' => 'prefetch', 'href' => "https://example.com/{$buildDir}/assets/Register-CfYQbTlA.js", 'fetchpriority' => 'low'],
            ['rel' => 'prefetch', 'href' => "https://example.com/{$buildDir}/assets/ResetPassword-BNl7a4X1.js", 'fetchpriority' => 'low'],
            ['rel' => 'prefetch', 'href' => "https://example.com/{$buildDir}/assets/VerifyEmail-CyukB_SZ.js", 'fetchpriority' => 'low'],
            ['rel' => 'prefetch', 'href' => "https://example.com/{$buildDir}/assets/Dashboard-DM_LxQy2.js", 'fetchpriority' => 'low'],
            ['rel' => 'prefetch', 'href' => "https://example.com/{$buildDir}/assets/AuthenticatedLayout-DfWF52N1.js", 'fetchpriority' => 'low'],
            ['rel' => 'prefetch', 'href' => "https://example.com/{$buildDir}/assets/Edit-CYV2sXpe.js", 'fetchpriority' => 'low'],
            ['rel' => 'prefetch', 'href' => "https://example.com/{$buildDir}/assets/DeleteUserForm-B1oHFaVP.js", 'fetchpriority' => 'low'],
            ['rel' => 'prefetch', 'href' => "https://example.com/{$buildDir}/assets/UpdatePasswordForm-CaeWqGla.js", 'fetchpriority' => 'low'],
            ['rel' => 'prefetch', 'href' => "https://example.com/{$buildDir}/assets/UpdateProfileInformationForm-CJwkYwQQ.js", 'fetchpriority' => 'low'],
            ['rel' => 'prefetch', 'href' => "https://example.com/{$buildDir}/assets/Welcome-D_7l79PQ.js", 'fetchpriority' => 'low'],
            ['rel' => 'prefetch', 'href' => "https://example.com/{$buildDir}/assets/admin-runtime-import-CRvLQy6v.js", 'fetchpriority' => 'low'],
            ['rel' => 'prefetch', 'href' => "https://example.com/{$buildDir}/assets/admin-runtime-import-import-DKMIaPXC.js", 'fetchpriority' => 'low'],
            ['rel' => 'prefetch', 'as' => 'style', 'href' => "https://example.com/{$buildDir}/assets/admin-runtime-import-BlmN0T4U.css", 'fetchpriority' => 'low'],
        ]);
        $this->assertSame(<<<HTML
        <link rel="preload" as="style" href="https://example.com/{$buildDir}/assets/index-B3s1tYeC.css" /><link rel="preload" as="style" href="https://example.com/{$buildDir}/assets/admin-BctAalm_.css" /><link rel="modulepreload" href="https://example.com/{$buildDir}/assets/admin-Sefg0Q45.js" /><link rel="modulepreload" href="https://example.com/{$buildDir}/assets/index-BSdK3M0e.js" /><link rel="stylesheet" href="https://example.com/{$buildDir}/assets/index-B3s1tYeC.css" /><link rel="stylesheet" href="https://example.com/{$buildDir}/assets/admin-BctAalm_.css" /><script type="module" src="https://example.com/{$buildDir}/assets/admin-Sefg0Q45.js"></script>
        <script>
             window.addEventListener('load', () => window.setTimeout(() => {
                const makeLink = (asset) => {
                    const link = document.createElement('link')

                    Object.keys(asset).forEach((attribute) => {
                        link.setAttribute(attribute, asset[attribute])
                    })

                    return link
                }

                const loadNext = (assets, count) => window.setTimeout(() => {
                    if (count > assets.length) {
                        count = assets.length

                        if (count === 0) {
                            return
                        }
                    }

                    const fragment = new DocumentFragment

                    while (count > 0) {
                        const link = makeLink(assets.shift())
                        fragment.append(link)
                        count--

                        if (assets.length) {
                            link.onload = () => loadNext(assets, 1)
                            link.onerror = () => loadNext(assets, 1)
                        }
                    }

                    document.head.append(fragment)
                })

                loadNext({$expectedAssets}, 3)
            }))
        </script>
        HTML, $html);

        $this->cleanViteManifest($buildDir);
    }

    public function testSupportCspNonceInPrefetchScript()
    {
        $manifest = json_decode(file_get_contents(__DIR__.'/fixtures/prefetching-manifest.json'));
        $buildDir = Str::random();
        $this->makeViteManifest($manifest, $buildDir);
        app()->usePublicPath(__DIR__);

        $html = (string) tap(ViteFacade::withEntryPoints(['resources/js/app.js']))
            ->useCspNonce('abc123')
            ->useBuildDirectory($buildDir)
            ->prefetch()
            ->toHtml();
        $this->assertStringContainsString('<script nonce="abc123">', $html);

        $html = (string) tap(ViteFacade::withEntryPoints(['resources/js/app.js']))
            ->useCspNonce('abc123')
            ->useBuildDirectory($buildDir)
            ->prefetch(concurrency: 3)
            ->toHtml();
        $this->assertStringContainsString('<script nonce="abc123">', $html);

        $this->cleanViteManifest($buildDir);
    }

    public function testItCanConfigureThePrefetchTriggerEvent()
    {
        $manifest = json_decode(file_get_contents(__DIR__.'/fixtures/prefetching-manifest.json'));
        $buildDir = Str::random();
        $this->makeViteManifest($manifest, $buildDir);
        app()->usePublicPath(__DIR__);

        $html = (string) tap(ViteFacade::withEntryPoints(['resources/js/app.js']))
            ->useBuildDirectory($buildDir)
            ->prefetch(event: 'vite:prefetch')
            ->toHtml();
        $this->assertStringNotContainsString("window.addEventListener('load', ", $html);
        $this->assertStringContainsString("window.addEventListener('vite:prefetch', ", $html);

        $this->cleanViteManifest($buildDir);
    }

    protected function cleanViteManifest($path = 'build')
    {
        if (file_exists(public_path("{$path}/manifest.json"))) {
            unlink(public_path("{$path}/manifest.json"));
        }

        if (file_exists(public_path($path))) {
            rmdir(public_path($path));
        }
    }

    protected function makeAsset($asset, $content)
    {
        $path = public_path('build/assets');

        if (! file_exists($path)) {
            mkdir($path, recursive: true);
        }

        file_put_contents($path.'/'.$asset, $content);
    }

    protected function cleanAsset($asset)
    {
        $path = public_path('build/assets');

        unlink($path.$asset);

        rmdir($path);
    }

    protected function makeViteHotFile($path = null)
    {
        app()->usePublicPath(__DIR__);

        $path ??= public_path('hot');

        file_put_contents($path, 'http://localhost:3000');
    }

    protected function cleanViteHotFile($path = null)
    {
        $path ??= public_path('hot');

        if (file_exists($path)) {
            unlink($path);
        }
    }
}
