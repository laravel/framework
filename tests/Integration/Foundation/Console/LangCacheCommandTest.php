<?php

namespace Illuminate\Tests\Integration\Foundation\Console;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Translation\CachedFileLoader;
use Illuminate\Translation\FileLoader;
use LogicException;
use Orchestra\Testbench\TestCase;

class LangCacheCommandTest extends TestCase
{
    protected $langPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->langPath = sys_get_temp_dir().'/laravel-lang-cache-'.uniqid();

        $this->writeFile("{$this->langPath}/lang/en/messages.php", "<?php return ['foo' => 'bar', 'nested' => ['a' => 'b']];");
        $this->writeFile("{$this->langPath}/lang/en/admin/users.php", "<?php return ['title' => 'Users'];");
        $this->writeFile("{$this->langPath}/lang/fr/messages.php", "<?php return ['foo' => 'le bar'];");
        $this->writeFile("{$this->langPath}/lang/en.json", json_encode(['Hello' => 'Hello', 'Bye' => 'Bye']));
        $this->writeFile("{$this->langPath}/lang/vendor/package/en/messages.php", "<?php return ['foo' => 'overridden'];");
        $this->writeFile("{$this->langPath}/package/en/messages.php", "<?php return ['foo' => 'package', 'bar' => 'baz'];");
        $this->writeFile("{$this->langPath}/json/en.json", json_encode(['Hello' => 'Package hello', 'Extra' => 'Extra']));

        $this->app->useLangPath("{$this->langPath}/lang");

        $this->app->forgetInstance('translator');
        $this->app->forgetInstance('translation.loader');

        $this->app['translator']->addNamespace('package', "{$this->langPath}/package");
        $this->app['translator']->addJsonPath("{$this->langPath}/json");
    }

    protected function tearDown(): void
    {
        (new Filesystem)->deleteDirectory($this->langPath);

        @unlink($this->app->getCachedTranslationsPath());

        parent::tearDown();
    }

    protected function writeFile($path, $contents)
    {
        $files = new Filesystem;

        $files->ensureDirectoryExists(dirname($path));

        $files->put($path, $contents);
    }

    public function testTranslationsAreCachedWithTheSameLinesAsTheFileLoader()
    {
        $loader = $this->app['translation.loader'];

        $this->artisan('lang:cache')->assertSuccessful();

        $this->assertFileExists($this->app->getCachedTranslationsPath());

        $cached = new CachedFileLoader(new Filesystem, $loader->paths(), require $this->app->getCachedTranslationsPath());

        $fresh = new FileLoader(new Filesystem, $loader->paths());
        $fresh->addNamespace('package', "{$this->langPath}/package");
        $fresh->addJsonPath("{$this->langPath}/json");

        foreach ([
            ['en', '*', '*'],
            ['en', 'messages', '*'],
            ['en', 'admin/users', '*'],
            ['en', 'validation', '*'],
            ['en', 'missing', '*'],
            ['en', 'messages', 'package'],
            ['fr', 'messages', '*'],
            ['fr', '*', '*'],
            ['fr', 'messages', 'package'],
        ] as [$locale, $group, $namespace]) {
            $this->assertSame(
                $fresh->load($locale, $group, $namespace),
                $cached->load($locale, $group, $namespace),
                "Cached lines differ for [{$locale}] [{$namespace}::{$group}]."
            );
        }

        $this->assertSame('overridden', $cached->load('en', 'messages', 'package')['foo']);
        $this->assertSame('Hello', $cached->load('en', '*', '*')['Hello']);
        $this->assertSame('Extra', $cached->load('en', '*', '*')['Extra']);
    }

    public function testTranslatorUsesTheFileLoaderWhenTranslationsAreNotCached()
    {
        $this->assertFalse($this->app->translationsAreCached());
        $this->assertSame(FileLoader::class, get_class($this->app['translation.loader']));
        $this->assertSame('bar', __('messages.foo'));
        $this->assertSame('Hello', __('Hello'));
    }

    public function testTranslatorUsesTheCacheWhenTranslationsAreCached()
    {
        $this->artisan('lang:cache')->assertSuccessful();

        $this->app->forgetInstance('translations.cached');
        $this->app->forgetInstance('translator');
        $this->app->forgetInstance('translation.loader');

        $this->assertInstanceOf(CachedFileLoader::class, $this->app['translation.loader']);
        $this->assertSame('bar', __('messages.foo'));
        $this->assertSame('Users', __('admin/users.title'));
        $this->assertSame('le bar', __('messages.foo', [], 'fr'));
        $this->assertSame('Hello', __('Hello'));
        $this->assertSame('overridden', __('package::messages.foo'));
        $this->assertSame('baz', __('package::messages.bar'));
    }

    public function testCacheCanBeCleared()
    {
        $this->artisan('lang:cache')->assertSuccessful();
        $this->assertFileExists($this->app->getCachedTranslationsPath());

        $this->artisan('lang:clear')->assertSuccessful();
        $this->assertFileDoesNotExist($this->app->getCachedTranslationsPath());
    }

    public function testNonSerializableTranslationsAreNotCached()
    {
        $this->writeFile("{$this->langPath}/lang/en/closure.php", "<?php return ['foo' => fn () => 'bar'];");

        try {
            $this->artisan('lang:cache');

            $this->fail('Expected a LogicException.');
        } catch (LogicException $e) {
            $this->assertSame('Your translation files are not serializable.', $e->getMessage());
        }

        $this->assertFileDoesNotExist($this->app->getCachedTranslationsPath());
    }
}
