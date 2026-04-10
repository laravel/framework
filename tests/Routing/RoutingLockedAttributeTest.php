<?php

namespace Illuminate\Tests\Routing;

use Illuminate\Routing\Attributes\Controllers\Locked;
use Illuminate\Routing\Middleware\HandleAtomicLocks;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Orchestra\Testbench\TestCase;

class RoutingLockedAttributeTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [\Illuminate\Routing\RoutingServiceProvider::class];
    }

    public function test_route_can_be_locked_via_attribute()
    {
        Cache::shouldReceive('lock')
            ->once()
            ->withAnyArgs()
            ->andReturn($lock = \Mockery::mock());

        $lock->shouldReceive('get')->once()->andReturn(true);
        $lock->shouldReceive('release')->once();

        Route::get('/withdraw', [LockedControllerStub::class, 'withdraw'])
            ->middleware(HandleAtomicLocks::class);

        $response = $this->get('/withdraw');

        $response->assertOk();
        $response->assertSee('success');
    }

    public function test_route_returns_423_when_locked()
    {
        Cache::shouldReceive('lock')
            ->once()
            ->withAnyArgs()
            ->andReturn($lock = \Mockery::mock());

        $lock->shouldReceive('get')->once()->andReturn(false);

        Route::get('/withdraw-locked', [LockedControllerStub::class, 'withdraw'])
            ->middleware(HandleAtomicLocks::class);

        $response = $this->get('/withdraw-locked');

        $response->assertStatus(423);
    }

    public function test_it_locks_unnamed_routes_successfully()
    {
        Cache::shouldReceive('lock')
            ->once()
            ->withAnyArgs()
            ->andReturn($lock = \Mockery::mock());

        $lock->shouldReceive('get')->once()->andReturn(true);
        $lock->shouldReceive('release')->once();

        Route::get('/test-unnamed', [LockedControllerStub::class, 'withdraw'])
            ->middleware(HandleAtomicLocks::class);

        $response = $this->get('/test-unnamed');

        $response->assertOk();
    }
}

class LockedControllerStub
{
    #[Locked(seconds: 10)]
    public function withdraw()
    {
        return 'success';
    }
}
