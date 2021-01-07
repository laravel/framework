<?php

namespace Illuminate\Tests\Foundation;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Testing\ParallelTesting;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class ParallelTestingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $_SERVER['LARAVEL_PARALLEL_TESTING'] = 1;
    }

    public function testAddTokenIfNeeded()
    {
        $this->assertSame(
            'my_local_storage',
            (new ParallelTesting())->addTokenIfNeeded('my_local_storage')
        );

        ParallelTesting::resolveTokenUsing(function () {
            return 1;
        });

        $this->assertSame(
            'my_local_storage_test_1',
            (new ParallelTesting())->addTokenIfNeeded('my_local_storage')
        );
    }

    public function tearDown(): void
    {
        parent::tearDown();

        m::close();
        ParallelTesting::resolveTokenUsing(null);
        unset($_SERVER['LARAVEL_PARALLEL_TESTING']);
    }
}
