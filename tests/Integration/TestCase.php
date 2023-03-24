<?php

namespace Illuminate\Tests\Integration;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Pagination\PaginationState;
use Illuminate\Support\Facades\Facade;
use Orchestra\Testbench\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
    /**
     * Get Application's base path.
     */
    public static function applicationBasePath(): string
    {
        if (! isset($_SERVER['TEST_TOKEN'])) {
            return parent::applicationBasePath();
        }

        $fs = new Filesystem();

        $applicationBasePath = parent::applicationBasePath();
        $workerApplicationBasePath = $applicationBasePath.'_'.$_SERVER['TEST_TOKEN'];

        if (! $fs->exists($workerApplicationBasePath)) {
            $fs->copyDirectory($applicationBasePath, $workerApplicationBasePath);
        }

        return $workerApplicationBasePath;
    }

    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
        $this->beforeApplicationDestroyed(function () {
            PaginationState::forgetApp();
        });

        parent::setUp();
    }
}
