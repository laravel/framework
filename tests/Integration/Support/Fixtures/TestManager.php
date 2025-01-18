<?php

declare(strict_types=1);

namespace Illuminate\Tests\Integration\Support\Fixtures;

use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Manager;

class TestManager extends Manager
{
    public function getDefaultDriver()
    {
        return 'test';
    }

    protected function createTestDriver()
    {
        return fn () => 'test';
    }

    protected function createNullDriver()
    {
        return fn () => null;
    }

    protected function createParametersDriver(Container $container)
    {
        return fn () => $container;
    }
}
