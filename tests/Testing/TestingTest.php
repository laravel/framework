<?php

namespace Illuminate\Tests\Foundation;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Testing\Testing;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class TestingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        putenv('LARAVEL_PARALLEL_TESTING=1');
    }

    public function testWhenRunningInParallel()
    {
        $app = m::mock(Application::class);

        $app->shouldReceive('runningUnitTests')
            ->once()
            ->andReturn(false);

        $testing = new Testing($app);
        $run = false;

        $testing->whenRunningInParallel(function () use (&$run) {
            $run = true;
        });
        $this->assertFalse($run);

        $app->shouldReceive('runningUnitTests')
            ->once()
            ->andReturn(true);

        Testing::tokenResolver(function () {
            return 1;
        });

        $testing->whenRunningInParallel(function () use (&$run) {
            $run = true;
        });
        $this->assertTrue($run);
    }

    public function testAddTokenIfNeeded()
    {
        $app = m::mock(Application::class);

        $app->shouldReceive('runningUnitTests')
            ->once()
            ->andReturn(false);

        $this->assertSame(
            'my_local_storage',
            (new Testing($app))->addTokenIfNeeded('my_local_storage')
        );

        $app->shouldReceive('runningUnitTests')
            ->once()
            ->andReturn(true);

        Testing::tokenResolver(function () {
            return 1;
        });

        $this->assertSame(
            'my_local_storage_test_1',
            (new Testing($app))->addTokenIfNeeded('my_local_storage')
        );
    }

    public function tearDown(): void
    {
        parent::tearDown();

        m::close();
        Testing::tokenResolver(null);
        putenv('LARAVEL_PARALLEL_TESTING=0');
    }
}
