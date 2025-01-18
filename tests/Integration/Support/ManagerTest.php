<?php

namespace Illuminate\Tests\Integration\Support;

use Illuminate\Contracts\Container\Container;
use Illuminate\Tests\Integration\Support\Fixtures\NullableManager;
use Illuminate\Tests\Integration\Support\Fixtures\TestManager;
use InvalidArgumentException;
use Orchestra\Testbench\TestCase;

class ManagerTest extends TestCase
{
    public function testDefaultDriverCannotBeNull()
    {
        $this->expectException(InvalidArgumentException::class);

        (new NullableManager($this->app))->driver();
    }

    public function testParametersCanBeInjectedToDriverCreator()
    {
        $manager = app(TestManager::class);

        $callback = $manager->driver('parameters');

        $this->assertInstanceOf(Container::class, $callback());
    }
}
