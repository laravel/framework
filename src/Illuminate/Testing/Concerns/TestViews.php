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
        $path = $this->testCompiledViewPath();

        if ($path) {
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
        $path = $this->testCompiledViewPath();

        if ($path) {
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
        if (! isset(self::$originalCompiledViewPath)) {
            self::$originalCompiledViewPath = $this->app['config']->get('view.compiled', '');
        }

        $path = self::$originalCompiledViewPath;

        if (! $path) {
            return null;
        }

        $token = ParallelTesting::token();

        return rtrim($path, '\/').'/'.'test_'.$token;
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
