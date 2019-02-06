<?php

namespace Illuminate\Tests\Integration\Support;

use InvalidArgumentException;
use Orchestra\Testbench\TestCase;

class ManagerTest extends TestCase
{
    public function testDefaultDriverCannotBeNull()
    {
        $this->expectException(InvalidArgumentException::class);

        (new Fixtures\NullableManager($this->app))->driver();
    }
}
