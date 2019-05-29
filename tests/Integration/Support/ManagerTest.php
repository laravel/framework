<?php

namespace Illuminate\Tests\Integration\Support;

use Orchestra\Testbench\TestCase;

class ManagerTest extends TestCase
{
    /**
     * @expectedException \InvalidArgumentException
     */
    public function testDefaultDriverCannotBeNull()
    {
        (new Fixtures\NullableManager($this->app))->driver();
    }
}
