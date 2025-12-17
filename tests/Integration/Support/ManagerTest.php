<?php

namespace Illuminate\Tests\Integration\Support;

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

    public function testCustomDriverClosureBoundObjectIsManager()
    {
        $manager = new NullableManager($this->app);
        $manager->extend(__CLASS__, fn () => $this);
        $this->assertSame($manager, $manager->driver(__CLASS__));
    }
}
