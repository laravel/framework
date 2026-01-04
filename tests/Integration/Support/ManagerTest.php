<?php

namespace Illuminate\Tests\Integration\Support;

use Illuminate\Tests\Integration\Support\Fixtures\EnumManager;
use Illuminate\Tests\Integration\Support\Fixtures\EnumManager\Bar;
use Illuminate\Tests\Integration\Support\Fixtures\EnumManager\Foo;
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
        $driver = (new EnumManager($this->app))
            ->useAsDefault(Foo::MySql)
            ->driver();

        $this->assertEquals('@mysql', $driver->name);
    }

    public function testDefaultDriverCanBeBackedEnum()
    {
        $driver = (new EnumManager($this->app))
            ->useAsDefault(Bar::MariaDb)
            ->driver();

        $this->assertEquals('@mariadb', $driver->name);
    }

    public function testExtend()
    {
        $manager = (new NullableManager($this->app))
            ->extend('myDriver', static fn () => new class('@my-driver')
            {
                public function __construct(public readonly string $name)
                {
                }
            });

        $concrete = $manager->driver('myDriver');
        $this->assertEquals('@my-driver', $concrete->name);

        $manager->extend('myDriver', static fn () => new class('@my-driver-ng')
        {
            public function __construct(public readonly string $name)
            {
            }
        });

        $concrete = $manager->driver('myDriver');
        $this->assertEquals('@my-driver', $concrete->name);
    }

    public function testExtendUsingEnum()
    {
        $concrete = (new EnumManager($this->app))
            ->useAsDefault(Bar::MariaDb)
            ->extend(Bar::MariaDb, fn () => new class('@mariadb-extended')
            {
                public function __construct(public readonly string $name)
                {
                }
            })
            ->driver();
        $this->assertEquals('@mariadb-extended', $concrete->name);

        $concrete = (new EnumManager($this->app))
            ->useAsDefault(Foo::MySql)
            ->extend(Foo::MySql, fn () => new class('@mysql-extended')
            {
                public function __construct(public readonly string $name)
                {
                }
            })
            ->driver();
        $this->assertEquals('@mysql-extended', $concrete->name);
    }

    public function testOthers()
    {
        $manager = (new EnumManager($this->app))
            ->useAsDefault(Bar::MariaDb);

        $default = $manager->driver();
        $this->assertEquals('@mariadb', $default->name);

        $other = $manager->driver(Foo::MySql);
        $this->assertEquals('@mysql', $other->name);
    }
}
