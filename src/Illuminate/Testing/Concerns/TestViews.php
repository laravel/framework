<?php

namespace Illuminate\Testing\Concerns;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\ParallelTesting;

trait TestViews
{
    /**
     * The original compiled view path prior to appending the token.
     *
     * @var string|null
     */
    protected static $originalCompiledViewPath = null;

    /**
     * Boot test views for parallel testing.
     *
     * @return void
     */
    protected function bootTestViews()
    {
        ParallelTesting::setUpProcess(function () {
            $this->setUpParallelTestingViewDirectory();
        });

        ParallelTesting::setUpTestCase(function () {
            $this->setUpParallelTestingViews();
        });
    }

    /**
     * Create the parallel testing view directory.
     *
     * @return void
     */
    protected function setUpParallelTestingViewDirectory()
    {
        if ($path = $this->testCompiledViewPath()) {
            File::ensureDirectoryExists($path);
        }
    }

    /**
     * Set up parallel testing views for the current test case.
     *
     * @return void
     */
    protected function setUpParallelTestingViews()
    {
        if ($path = $this->testCompiledViewPath()) {
            $this->switchToCompiledViewPath($path);
        }
    }

    /**
     * Returns the test compiled view path.
     *
     * @return string|null
     */
    protected function testCompiledViewPath()
    {
        self::$originalCompiledViewPath ??= $this->app['config']->get('view.compiled', '');

        if (! self::$originalCompiledViewPath) {
            return null;
        }

        $token = ParallelTesting::token();

        return rtrim(self::$originalCompiledViewPath, '\/').'/test_'.$token;
    }

    /**
     * Switch to the given compiled view path.
     *
     * @param  string  $path
     * @return void
     */
    protected function switchToCompiledViewPath($path)
    {
        $this->app['config']->set('view.compiled', $path);

        if ($this->app->resolved('blade.compiler')) {
            $compiler = $this->app['blade.compiler'];

            (function () use ($path) {
                $this->cachePath = $path;
            })->bindTo($compiler, $compiler)();
        }
    }
}
