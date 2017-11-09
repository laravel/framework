<?php

namespace Illuminate\Tests\Integration\Support;

use Orchestra\Testbench\TestCase;

class ManagerTest extends TestCase
{
    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Unable to resolve NULL driver for Illuminate\Tests\Integration\Support\Fixtures\NullableManager
     */
    public function testDefaultDriverCannotBeNull()
    {
        (new Fixtures\NullableManager($this->app))->driver();
    }
}
