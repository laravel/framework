<?php

namespace Illuminate\Tests\Integration\Routing;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Orchestra\Testbench\TestCase;
use User;

class RouteCanAnyBackedEnumTest extends TestCase
{
    public function testGuestForbiddenWhenAllAbilitiesFail()
    {
        Gate::define(AbilityBackedEnum::NotAccessRoute, fn (?User $user) => false);
        Gate::define(AbilityBackedEnum::AccessRoute, fn (?User $user) => false);

        $route = Route::get('/', fn () => 'Hello World')
            ->canAny([AbilityBackedEnum::NotAccessRoute, AbilityBackedEnum::AccessRoute]);

        $this->assertEquals(['can.any:not-access-route|access-route'], $route->middleware());

        $this->get('/')->assertForbidden();
    }

    public function testGuestAllowedWhenOneAbilityPasses()
    {
        Gate::define(AbilityBackedEnum::NotAccessRoute, fn (?User $user) => false);
        Gate::define(AbilityBackedEnum::AccessRoute, fn (?User $user) => true);

        $route = Route::get('/', fn () => 'Hello World')
            ->canAny([AbilityBackedEnum::NotAccessRoute, AbilityBackedEnum::AccessRoute]);

        $this->assertEquals(['can.any:not-access-route|access-route'], $route->middleware());

        $response = $this->get('/');
        $response->assertOk();
        $response->assertContent('Hello World');
    }
}
