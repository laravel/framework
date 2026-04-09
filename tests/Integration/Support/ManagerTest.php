<?php

namespace Illuminate\Tests\Integration\Support;

use Illuminate\Tests\Integration\Support\Fixtures\NullableManager;
use InvalidArgumentException;
use Orchestra\Testbench\TestCase;
use stdClass;

class ManagerTest extends TestCase
{
    public function testDefaultDriverCannotBeNull()
    {
        $this->expectException(InvalidArgumentException::class);

        (new NullableManager($this->app))->driver();
    }

    public function testCustomDriverClosureBoundObjectIsManager()
    {
        $manager = new NullableManager($this->app);
        $manager->extend(__CLASS__, fn () => $this);
        $this->assertSame($manager, $manager->driver(__CLASS__));
    }

    public function testCustomDriverStaticClosure()
    {
        $manager = new NullableManager($this->app);
        $driver = new stdClass;

        $manager->extend(__CLASS__, static fn () => $driver);
        $this->assertSame($driver, $manager->driver(__CLASS__));
    }

    public function testInvokableObjectDriverClosure()
    {
        $manager = new NullableManager($this->app);
        $driver = new stdClass;
        $creator = new CustomDriver($driver);

        $manager->extend(__CLASS__, $creator(...));
        $this->assertSame($driver, $manager->driver(__CLASS__));
    }
}

class CustomDriver {
    public function __construct(private object $object) {}

    public function __invoke()
    {
        return $this->object;
    }
}
