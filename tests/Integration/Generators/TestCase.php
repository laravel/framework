<?php

namespace Illuminate\Tests\Integration\Generators;

use Composer\Autoload\ClassLoader;
use Orchestra\Testbench\Concerns\InteractsWithPublishedFiles;

abstract class TestCase extends \Orchestra\Testbench\TestCase
{
    use InteractsWithPublishedFiles;

    protected function setUp(): void
    {
        parent::setUp();

        // Register the App namespace with Composer's autoloader for the testbench laravel app
        $appPath = $this->app->path();
        foreach (ClassLoader::getRegisteredLoaders() as $loader) {
            $loader->addPsr4('App\\', [$appPath]);
        }
    }
}
