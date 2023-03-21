<?php

namespace Illuminate\Tests\Integration\Support;

use Illuminate\Tests\Integration\Support\Fixtures\NullableManager;
use Illuminate\Tests\Integration\TestCase;
use InvalidArgumentException;

class ManagerTest extends TestCase
{
    public function testDefaultDriverCannotBeNull()
    {
        $this->expectException(InvalidArgumentException::class);

        (new NullableManager($this->app))->driver();
    }
}
