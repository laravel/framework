<?php

namespace Illuminate\Tests\Integration\Support;

use Illuminate\Tests\Integration\Support\Fixtures\DummyInstanceFactory;
use Illuminate\Tests\Integration\Support\Fixtures\Enums\Bar;
use Illuminate\Tests\Integration\Support\Fixtures\Enums\Foo;
use Illuminate\Tests\Integration\Support\Fixtures\Manager;
use Illuminate\Tests\Integration\Support\Fixtures\NullableManager;
use InvalidArgumentException;
use Orchestra\Testbench\TestCase;

class ManagerTest extends TestCase
{
    public function testDefaultDriverCannotBeNull()
    {
        $this->expectException(InvalidArgumentException::class);

        (new NullableManager($this->app))->driver();
    }

    public function testDefaultDriverCanBeUnitEnum()
    {
        $driver = Manager::makeWithDefaultDriver($this->app, Foo::MySql)
            ->driver();

        $this->assertEquals('@mysql', $driver->name);
    }

    public function testDefaultDriverCanBeBackedEnum()
    {
        $driver = Manager::makeWithDefaultDriver($this->app, Bar::MariaDb)
            ->driver();

        $this->assertEquals('@mariadb', $driver->name);
    }

    public function testExtend()
    {
        $manager = (new NullableManager($this->app))
            ->extend(
                'myDriver',
                static fn () => DummyInstanceFactory::withName('@my-driver')
            );

        $concrete = $manager->driver('myDriver');
        $this->assertEquals('@my-driver', $concrete->name);

        $manager->extend('myDriver', static fn () => DummyInstanceFactory::withName('@my-driver-overrode'));
        $cachedConcrete = $manager->driver('myDriver');
        $this->assertEquals('@my-driver', $cachedConcrete->name);

        $manager->forgetDrivers();
        $concrete = $manager->driver('myDriver');
        $this->assertEquals('@my-driver-overrode', $concrete->name);

        $cachedConcrete = $manager->driver('myDriver');
        $this->assertEquals(spl_object_hash($concrete), spl_object_hash($cachedConcrete));
    }

    public function testExtendUsingEnum()
    {
        $concrete = Manager::makeWithDefaultDriver($this->app, Bar::MariaDb)
            ->extend(Bar::MariaDb, static fn () => DummyInstanceFactory::withName('@mariadb-extended'))
            ->driver();
        $this->assertEquals('@mariadb-extended', $concrete->name);

        $concrete = Manager::makeWithDefaultDriver($this->app, Foo::MySql)
            ->extend(Foo::MySql, fn () => DummyInstanceFactory::withName('@mysql-extended'))
            ->driver();
        $this->assertEquals('@mysql-extended', $concrete->name);
    }

    public function testOthers()
    {
        $manager = Manager::makeWithDefaultDriver($this->app, Bar::MariaDb);

        $default = $manager->driver();
        $this->assertEquals('@mariadb', $default->name);

        $other = $manager->driver(Foo::MySql);
        $this->assertEquals('@mysql', $other->name);
    }
}
