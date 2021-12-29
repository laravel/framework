<?php

namespace Illuminate\Tests\Integration\Routing;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Orchestra\Testbench\TestCase;

class RouteViewTest extends TestCase
{
    protected function defineDatabaseMigrations(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function testRouteView()
    {
        Route::view('route', 'view', ['foo' => 'bar']);

        View::addLocation(__DIR__.'/Fixtures');

        $this->assertStringContainsString('Test bar', $this->get('/route')->getContent());
        $this->assertSame(200, $this->get('/route')->status());
    }

    public function testRouteViewWithParams()
    {
        Route::view('route/{param}/{param2?}', 'view', ['foo' => 'bar']);

        View::addLocation(__DIR__.'/Fixtures');

        $this->assertStringContainsString('Test bar', $this->get('/route/value1/value2')->getContent());
        $this->assertStringContainsString('Test bar', $this->get('/route/value1')->getContent());
    }

    public function testRouteViewWithStatus()
    {
        Route::view('route', 'view', ['foo' => 'bar'], 418);

        View::addLocation(__DIR__.'/Fixtures');

        $this->assertSame(418, $this->get('/route')->status());
    }

    public function testRouteViewWithHeaders()
    {
        Route::view('route', 'view', ['foo' => 'bar'], 418, ['Framework' => 'Laravel']);

        View::addLocation(__DIR__.'/Fixtures');

        $this->assertSame('Laravel', $this->get('/route')->headers->get('Framework'));
    }

    public function testRouteViewOverloadingStatusWithHeaders()
    {
        Route::view('route', 'view', ['foo' => 'bar'], ['Framework' => 'Laravel']);

        View::addLocation(__DIR__.'/Fixtures');

        $this->assertSame('Laravel', $this->get('/route')->headers->get('Framework'));
    }

    public function testRouteViewInjectsParametersAsData()
    {
        $user = \App\Models\User::create(['name' => 'Dries']);

        config(['app.key' => str_repeat('a', 32)]);

        Route::bind('foo', function ($value) {
            return $value.'baz';
        });
        Route::view('/bindings/{user}/{foo}', 'implicit_bindings')->middleware('web');

        View::addLocation(__DIR__.'/Fixtures');

        $response = $this->get("/bindings/{$user->id}/bar");

        $this->assertEquals('Hello Dries, barbaz!', trim($response->content()));
    }

    public function testRouteViewDoesNotOverwriteDataWithParameters()
    {
        $user = \App\Models\User::create(['name' => 'Jane']);

        config(['app.key' => str_repeat('a', 32)]);

        Route::bind('foo', function ($value) {
            return $value.'baz';
        });
        Route::view('/bindings/{user}/{foo}', 'implicit_bindings', ['foo' => 'huzzah'])->middleware('web');

        View::addLocation(__DIR__.'/Fixtures');

        $response = $this->get("/bindings/{$user->id}/baz");

        $this->assertEquals('Hello Jane, huzzah!', trim($response->content()));
    }
}
