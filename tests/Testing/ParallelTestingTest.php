<?php

namespace Illuminate\Tests\Foundation;

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

    /**
     * @dataProvider callbacks
     */
    public function testCallbacks($callback)
    {
        $parallelTesting = new ParallelTesting();
        $caller = 'call'.ucfirst($callback).'Callbacks';

        $state = false;
        $parallelTesting->{$caller}($this);
        $this->assertFalse($state);

        $parallelTesting->{$callback}(function () use (&$state) {
            $state = true;
        });

        $parallelTesting->{$caller}($this);
        $this->assertFalse($state);

        $parallelTesting->resolveTokenUsing(function () {
            return 1;
        });

        $parallelTesting->{$caller}($this);
        $this->assertTrue($state);
    }

    public function testToken()
    {
        $parallelTesting = new ParallelTesting();

        $this->assertFalse($parallelTesting->token());

        $parallelTesting->resolveTokenUsing(function () {
            return 1;
        });

        $this->assertSame(1, $parallelTesting->token());
    }

    public function callbacks()
    {
        return [
            ['setUpProcess'],
            ['setUpTestCase'],
            ['tearDownTestCase'],
            ['tearDownProcess'],
        ];
    }

    public function tearDown(): void
    {
        parent::tearDown();

        m::close();
        unset($_SERVER['LARAVEL_PARALLEL_TESTING']);
    }
}
