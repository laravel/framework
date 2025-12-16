<?php

namespace Illuminate\Tests\Integration\Generators;

use Composer\Autoload\ClassLoader;
use Orchestra\Testbench\Concerns\InteractsWithPublishedFiles;

use function Orchestra\Testbench\default_skeleton_path;

abstract class TestCase extends \Orchestra\Testbench\TestCase
{
    use InteractsWithPublishedFiles;

    protected function setUp(): void
    {
        foreach (ClassLoader::getRegisteredLoaders() as $loader) {
            $loader->addPsr4('App\\', [default_skeleton_path('app')]);
        }
        parent::setUp();
    }
}
