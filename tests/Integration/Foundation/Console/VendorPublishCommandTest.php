<?php

namespace Illuminate\Tests\Integration\Foundation\Console;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;
use Orchestra\Testbench\TestCase;

class VendorPublishCommandTest extends TestCase
{
    protected Filesystem $files;

    protected function setUp(): void
    {
        $this->files = new Filesystem;

        $this->afterApplicationCreated(function () {
            $this->files->ensureDirectoryExists($this->app->basePath('vendor-publish-test/package/config'));
            $this->files->ensureDirectoryExists($this->app->basePath('vendor-publish-test/app'));

            VendorPublishMergeTestServiceProvider::$paths = [
                $this->packagePath('config/package.php') => $this->appPath('package.php'),
                $this->packagePath('config') => $this->appPath('config'),
                $this->packagePath('views/welcome.blade.php') => $this->appPath('welcome.blade.php'),
            ];

            $this->app->register(VendorPublishMergeTestServiceProvider::class);
        });

        $this->beforeApplicationDestroyed(function () {
            $this->files->deleteDirectory($this->app->basePath('vendor-publish-test'));

            unset(
                ServiceProvider::$publishes[VendorPublishMergeTestServiceProvider::class],
                ServiceProvider::$publishGroups['merge-test'],
            );
        });

        parent::setUp();
    }

    public function testMergeAddsMissingKeysToPublishedFilesAndKeepsExistingValues()
    {
        $this->files->put($this->packagePath('config/package.php'), $this->config(['driver' => "'file'", 'timeout' => '60']));
        $this->files->put($this->appPath('package.php'), $this->config(['driver' => "'redis'"]));

        $this->artisan('vendor:publish', ['--tag' => 'merge-test', '--merge' => true])
            ->expectsOutputToContain('Merging file')
            ->assertOk();

        $this->assertSame(
            $this->config(['driver' => "'redis'", 'timeout' => '60']),
            $this->files->get($this->appPath('package.php')),
        );
    }

    public function testMergeSkipsFilesThatAreAlreadyUpToDate()
    {
        $this->files->put($this->packagePath('config/package.php'), $this->config(['driver' => "'file'"]));
        $this->files->put($this->appPath('package.php'), $this->config(['driver' => "'redis'", 'extra' => 'true']));

        $this->artisan('vendor:publish', ['--tag' => 'merge-test', '--merge' => true])
            ->expectsOutputToContain('is already up to date')
            ->assertOk();

        $this->assertSame(
            $this->config(['driver' => "'redis'", 'extra' => 'true']),
            $this->files->get($this->appPath('package.php')),
        );
    }

    public function testMergePublishesFilesThatDoNotExistYet()
    {
        $this->files->put($this->packagePath('config/package.php'), $this->config(['driver' => "'file'"]));

        $this->artisan('vendor:publish', ['--tag' => 'merge-test', '--merge' => true])->assertOk();

        $this->assertSame($this->config(['driver' => "'file'"]), $this->files->get($this->appPath('package.php')));
    }

    public function testMergeWithExistingDoesNotPublishFilesThatDoNotExistYet()
    {
        $this->files->put($this->packagePath('config/package.php'), $this->config(['driver' => "'file'"]));

        $this->artisan('vendor:publish', ['--tag' => 'merge-test', '--merge' => true, '--existing' => true])->assertOk();

        $this->assertFileDoesNotExist($this->appPath('package.php'));
    }

    public function testMergeFallsBackToTheDefaultBehaviorForFilesThatCannotBeMerged()
    {
        $this->files->ensureDirectoryExists($this->packagePath('views'));
        $this->files->put($this->packagePath('views/welcome.blade.php'), 'new');
        $this->files->put($this->appPath('welcome.blade.php'), 'mine');

        $this->artisan('vendor:publish', ['--tag' => 'merge-test', '--merge' => true])
            ->expectsOutputToContain('already exists')
            ->assertOk();

        $this->assertSame('mine', $this->files->get($this->appPath('welcome.blade.php')));

        $this->artisan('vendor:publish', ['--tag' => 'merge-test', '--merge' => true, '--force' => true])->assertOk();

        $this->assertSame('new', $this->files->get($this->appPath('welcome.blade.php')));
    }

    public function testMergeAppliesToFilesWithinPublishedDirectories()
    {
        $this->files->put($this->packagePath('config/package.php'), $this->config(['driver' => "'file'", 'timeout' => '60']));
        $this->files->ensureDirectoryExists($this->appPath('config'));
        $this->files->put($this->appPath('config/package.php'), $this->config(['driver' => "'redis'"]));

        $this->artisan('vendor:publish', ['--tag' => 'merge-test', '--merge' => true])->assertOk();

        $this->assertSame(
            $this->config(['driver' => "'redis'", 'timeout' => '60']),
            $this->files->get($this->appPath('config/package.php')),
        );
    }

    public function testWithoutMergeExistingFilesAreNotChanged()
    {
        $this->files->put($this->packagePath('config/package.php'), $this->config(['driver' => "'file'", 'timeout' => '60']));
        $this->files->put($this->appPath('package.php'), $this->config(['driver' => "'redis'"]));

        $this->artisan('vendor:publish', ['--tag' => 'merge-test'])->assertOk();

        $this->assertSame($this->config(['driver' => "'redis'"]), $this->files->get($this->appPath('package.php')));
    }

    protected function config(array $values)
    {
        $entries = '';

        foreach ($values as $key => $value) {
            $entries .= "    '{$key}' => {$value},\n";
        }

        return "<?php\n\nreturn [\n{$entries}];\n";
    }

    protected function packagePath(string $path)
    {
        return $this->app->basePath('vendor-publish-test/package/'.$path);
    }

    protected function appPath(string $path)
    {
        return $this->app->basePath('vendor-publish-test/app/'.$path);
    }
}

class VendorPublishMergeTestServiceProvider extends ServiceProvider
{
    public static array $paths = [];

    public function boot()
    {
        $this->publishes(static::$paths, 'merge-test');
    }
}
