<?php

namespace Illuminate\Foundation;

use PHPUnit\Framework\TestCase;

class FoundationViteTest extends TestCase
{
    public function testViteWithoutCss()
    {
        $this->makeViteManifest();

        $result = (new Vite)(['resources/js/app-without-css.js']);

        $this->cleanViteManifest();

        $this->assertSame('<script type="module" src="/build/assets/app-without-css.versioned.js"></script>', $result->toHtml());
    }

    public function testViteWithCss()
    {
        $this->makeViteManifest();

        $result = (new Vite)(['resources/js/app-with-css.js']);

        $this->cleanViteManifest();

        $this->assertSame(
            '<link rel="stylesheet" href="/build/assets/app.versioned.css" />'
            . '<script type="module" src="/build/assets/app-with-css.versioned.js"></script>',
            $result->toHtml()
        );
    }

    public function testViteWithSharedCss()
    {
        $this->makeViteManifest();

        $result = (new Vite)(['resources/js/app-with-shared-css.js']);

        $this->cleanViteManifest();

        $this->assertSame(
            '<link rel="stylesheet" href="/build/assets/app.versioned.css" />'
            . '<script type="module" src="/build/assets/app-with-shared-css.versioned.js"></script>',
            $result->toHtml()
        );
    }

    public function testViteHotModuleReplacement()
    {
        $this->makeViteHotFile();

        $result = (new Vite)(['resources/js/app-with-css.js']);

        $this->cleanViteHotFile();

        $this->assertSame(
            '<script type="module" src="http://localhost:3000/@vite/client"></script>'
            . '<script type="module" src="http://localhost:3000/resources/js/app-with-css.js"></script>',
            $result->toHtml()
        );
    }

    protected function makeViteManifest()
    {
        app()->singleton('path.public', fn () => __DIR__);

        if (! file_exists(public_path('build'))) {
            mkdir(public_path('build'));
        }

        $manifest = json_encode([
            'resources/js/app-without-css.js' => [
                'file' => 'assets/app-without-css.versioned.js',
            ],
            'resources/js/app-with-css.js' => [
                'file' => 'assets/app-with-css.versioned.js',
                'css' => [
                    'assets/app.versioned.css',
                ],
            ],
            'resources/js/app-with-shared-css.js' => [
                'file' => 'assets/app-with-shared-css.versioned.js',
                'imports' => [
                    '_someFile.js',
                ],
            ],
            '_someFile.js' => [
                'css' => [
                    'assets/app.versioned.css',
                ],
            ],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        file_put_contents(public_path('build/manifest.json'), $manifest);
    }

    protected function cleanViteManifest()
    {
        unlink(public_path('build/manifest.json'));
        rmdir(public_path('build'));
    }

    protected function makeViteHotFile()
    {
        app()->singleton('path.public', fn () => __DIR__);

        file_put_contents(public_path('hot'), 'http://localhost:3000');
    }

    protected function cleanViteHotFile()
    {
        unlink(public_path('hot'));
    }
}
