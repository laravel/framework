<?php

namespace Illuminate\Tests\Integration\Support;

use InvalidArgumentException;
use Orchestra\Testbench\TestCase;
use Illuminate\Tests\Integration\Support\Fixtures\NullableManager;

class ManagerTest extends TestCase
{
    public function testDefaultDriverCannotBeNull()
    {
        $this->expectException(InvalidArgumentException::class);

        (new NullableManager($this->app))->driver();
    }
}
