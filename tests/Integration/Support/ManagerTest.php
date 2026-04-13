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

    public function testEnumDriverCanBeResolved()
    {
        $manager = new NullableManager($this->app);
        $driver = new stdClass;

        $manager->extend('my_driver', static fn () => $driver);
        $this->assertSame($driver, $manager->driver(ManagerDriverName::MyDriver));
    }

    public function testEnumDriverIsCached()
    {
        $manager = new NullableManager($this->app);

        $manager->extend('my_driver', static fn () => new stdClass);

        $driver1 = $manager->driver(ManagerDriverName::MyDriver);
        $driver2 = $manager->driver(ManagerDriverName::MyDriver);

        $this->assertSame($driver1, $driver2);
    }

    public function testEnumDriverMatchesStringDriver()
    {
        $manager = new NullableManager($this->app);

        $manager->extend('my_driver', static fn () => new stdClass);

        $fromEnum = $manager->driver(ManagerDriverName::MyDriver);
        $fromString = $manager->driver('my_driver');

        $this->assertSame($fromEnum, $fromString);
    }

    public function testUnitEnumDriverCanBeResolved()
    {
        $manager = new NullableManager($this->app);
        $driver = new stdClass;

        $manager->extend('MyDriver', static fn () => $driver);
        $this->assertSame($driver, $manager->driver(ManagerUnitDriverName::MyDriver));
    }
}

enum ManagerDriverName: string
{
    case MyDriver = 'my_driver';
}

enum ManagerUnitDriverName
{
    case MyDriver;
}
