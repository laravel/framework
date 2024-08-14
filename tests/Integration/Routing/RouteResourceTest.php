<?php

namespace Illuminate\Tests\Integration\Routing;

use Illuminate\Support\Facades\Route;
use Illuminate\Tests\Integration\Routing\Fixtures\ResourceTestController;
use Orchestra\Testbench\TestCase;

class RouteResourceTest extends TestCase
{
    public function testResourceRoutes()
    {
        Route::resource('organizations', ResourceTestController::class);

        $this->assertCount(7, Route::getRoutes()->getRoutes());

        $this->assertSame('http://localhost/organizations', route('organizations.index'));
        $this->get(route('organizations.index'))->assertOk()->assertSee('resource index');

        $this->assertSame('http://localhost/organizations/123', route('organizations.show', '123'));
        $this->get(route('organizations.show', '123'))->assertOk()->assertSee('resource show for 123');

        $this->assertSame('http://localhost/organizations/create', route('organizations.create'));
        $this->get(route('organizations.create'))->assertOk()->assertSee('resource create');

        $this->assertSame('http://localhost/organizations', route('organizations.store'));
        $this->post(route('organizations.store'))->assertOk()->assertSee('resource store');

        $this->assertSame('http://localhost/organizations/123/edit', route('organizations.edit', '123'));
        $this->get(route('organizations.edit', '123'))->assertOk()->assertSee('resource edit for 123');

        $this->assertSame('http://localhost/organizations/123', route('organizations.update', '123'));
        $this->put(route('organizations.update', '123'))->assertOk()->assertSee('resource update for 123');
        $this->patch(route('organizations.update', '123'))->assertOk()->assertSee('resource update for 123');

        $this->assertSame('http://localhost/organizations/123', route('organizations.destroy', '123'));
        $this->delete(route('organizations.destroy', '123'))->assertOk()->assertSee('resource destroy for 123');
    }

    public function testRestorableResourceRoutes()
    {
        Route::resource('organizations', ResourceTestController::class)->restorable();

        $this->assertCount(8, Route::getRoutes()->getRoutes());

        $this->assertSame('http://localhost/organizations', route('organizations.index'));
        $this->get(route('organizations.index'))->assertOk()->assertSee('resource index');

        $this->assertSame('http://localhost/organizations/123', route('organizations.show', '123'));
        $this->get(route('organizations.show', '123'))->assertOk()->assertSee('resource show for 123');

        $this->assertSame('http://localhost/organizations/create', route('organizations.create'));
        $this->get(route('organizations.create'))->assertOk()->assertSee('resource create');

        $this->assertSame('http://localhost/organizations', route('organizations.store'));
        $this->post(route('organizations.store'))->assertOk()->assertSee('resource store');

        $this->assertSame('http://localhost/organizations/123/edit', route('organizations.edit', '123'));
        $this->get(route('organizations.edit', '123'))->assertOk()->assertSee('resource edit for 123');

        $this->assertSame('http://localhost/organizations/123', route('organizations.update', '123'));
        $this->put(route('organizations.update', '123'))->assertOk()->assertSee('resource update for 123');
        $this->patch(route('organizations.update', '123'))->assertOk()->assertSee('resource update for 123');

        $this->assertSame('http://localhost/organizations/123', route('organizations.destroy', '123'));
        $this->delete(route('organizations.destroy', '123'))->assertOk()->assertSee('resource destroy for 123');

        $this->assertSame('http://localhost/organizations/123/restore', route('organizations.restore', '123'));
        $this->post(route('organizations.restore', '123'))->assertOk()->assertSee('resource restore for 123');
    }
}
