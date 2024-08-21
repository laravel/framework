<?php

namespace Illuminate\Tests\Integration\Console;

use Illuminate\Support\Facades\Concurrency;
use Orchestra\Testbench\TestCase;

class ConcurrencyTest extends TestCase
{
    public function testWorkCanBeDistributed()
    {
        $this->markTestSkipped('Todo...');

        [$first, $second] = Concurrency::run([
            fn () => 1 + 1,
            fn () => 2 + 2,
        ]);

        $this->assertEquals(2, $first);
        $this->assertEquals(4, $second);
    }
}
