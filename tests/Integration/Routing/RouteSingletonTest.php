<?php

namespace Illuminate\Tests\Integration\Routing;

use Illuminate\Support\Facades\Route;
use Illuminate\Tests\Integration\Routing\Fixtures\CreatableSingletonTestController;
use Illuminate\Tests\Integration\Routing\Fixtures\NestedSingletonTestController;
use Illuminate\Tests\Integration\Routing\Fixtures\SingletonTestController;
use Orchestra\Testbench\TestCase;

class RouteSingletonTest extends TestCase
{
    public function testSingletonDefaults()
    {
        Route::singleton('avatar', SingletonTestController::class);

        $response = $this->get('/avatar/create');
        $this->assertEquals(404, $response->getStatusCode());

        $this->assertSame('http://localhost/avatar', route('avatar.show'));
        $response = $this->get('/avatar');
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertSame('singleton show', $response->getContent());

        $this->assertSame('http://localhost/avatar/edit', route('avatar.edit'));
        $response = $this->get('/avatar/edit');
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertSame('singleton edit', $response->getContent());

        $this->assertSame('http://localhost/avatar', route('avatar.update'));
        $response = $this->put('/avatar');
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertSame('singleton update', $response->getContent());

        $response = $this->patch('/avatar');
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertSame('singleton update', $response->getContent());

        // $this->assertSame('http://localhost/avatar', route('avatar.destroy'));
        // $response = $this->delete('/avatar');
        // $this->assertEquals(404, $response->getStatusCode());
        // $this->assertSame('singleton destroy', $response->getContent());
    }

    public function testCreatableSingleton()
    {
        Route::singleton('avatar', CreatableSingletonTestController::class)->creatable();

        $this->assertSame('http://localhost/avatar/create', route('avatar.create'));
        $response = $this->get('/avatar/create');
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertSame('singleton create', $response->getContent());

        $this->assertSame('http://localhost/avatar', route('avatar.store'));
        $response = $this->post('/avatar');
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertSame('singleton store', $response->getContent());

        $this->assertSame('http://localhost/avatar', route('avatar.destroy'));
        $response = $this->delete('/avatar');
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertSame('singleton destroy', $response->getContent());
    }

    public function testCreatableSingletonOnly()
    {
        Route::singleton('avatar', CreatableSingletonTestController::class)->creatable()->only('show');

        $response = $this->get('/avatar');
        $this->assertEquals(200, $response->getStatusCode());

        $response = $this->get('/avatar/create');
        $this->assertEquals(404, $response->getStatusCode());

        $response = $this->post('/avatar');
        $this->assertEquals(405, $response->getStatusCode());

        $response = $this->get('/avatar/edit');
        $this->assertEquals(404, $response->getStatusCode());

        $response = $this->put('/avatar');
        $this->assertEquals(405, $response->getStatusCode());

        $response = $this->patch('/avatar');
        $this->assertEquals(405, $response->getStatusCode());

        $response = $this->delete('/avatar');
        $this->assertEquals(405, $response->getStatusCode());
    }

    public function testCreatableSingletonExcept()
    {
        Route::singleton('avatar', CreatableSingletonTestController::class)->creatable()->except('show');

        $response = $this->get('/avatar');
        $this->assertEquals(405, $response->getStatusCode());

        $response = $this->get('/avatar/create');
        $this->assertEquals(200, $response->getStatusCode());

        $response = $this->post('/avatar');
        $this->assertEquals(200, $response->getStatusCode());

        $response = $this->get('/avatar/edit');
        $this->assertEquals(200, $response->getStatusCode());

        $response = $this->put('/avatar');
        $this->assertEquals(200, $response->getStatusCode());

        $response = $this->patch('/avatar');
        $this->assertEquals(200, $response->getStatusCode());

        $response = $this->delete('/avatar');
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testDestroyableSingleton()
    {
        Route::singleton('avatar', CreatableSingletonTestController::class)->destroyable();

        $this->assertSame('http://localhost/avatar', route('avatar.show'));
        $response = $this->get('/avatar');
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertSame('singleton show', $response->getContent());

        $this->assertSame('http://localhost/avatar/edit', route('avatar.edit'));
        $response = $this->get('/avatar/edit');
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertSame('singleton edit', $response->getContent());

        $this->assertSame('http://localhost/avatar', route('avatar.update'));
        $response = $this->put('/avatar');
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertSame('singleton update', $response->getContent());

        $this->assertSame('http://localhost/avatar', route('avatar.destroy'));
        $response = $this->delete('/avatar');
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertSame('singleton destroy', $response->getContent());
    }

    public function testDestroyableSingletonOnly()
    {
        Route::singleton('avatar', SingletonTestController::class)->destroyable()->only('destroy');

        $response = $this->get('/avatar');
        $this->assertEquals(405, $response->getStatusCode());

        $response = $this->get('/avatar/create');
        $this->assertEquals(404, $response->getStatusCode());

        $response = $this->post('/avatar');
        $this->assertEquals(405, $response->getStatusCode());

        $response = $this->get('/avatar/edit');
        $this->assertEquals(404, $response->getStatusCode());

        $response = $this->put('/avatar');
        $this->assertEquals(405, $response->getStatusCode());

        $response = $this->patch('/avatar');
        $this->assertEquals(405, $response->getStatusCode());

        $response = $this->delete('/avatar');
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testDestroyableSingletonExcept()
    {
        Route::singleton('avatar', SingletonTestController::class)->destroyable()->except('destroy');

        $response = $this->get('/avatar');
        $this->assertEquals(200, $response->getStatusCode());

        $response = $this->get('/avatar/create');
        $this->assertEquals(404, $response->getStatusCode());

        $response = $this->post('/avatar');
        $this->assertEquals(405, $response->getStatusCode());

        $response = $this->get('/avatar/edit');
        $this->assertEquals(200, $response->getStatusCode());

        $response = $this->put('/avatar');
        $this->assertEquals(200, $response->getStatusCode());

        $response = $this->patch('/avatar');
        $this->assertEquals(200, $response->getStatusCode());

        $response = $this->delete('/avatar');
        $this->assertEquals(405, $response->getStatusCode());
    }

    public function testCreatableDestroyableSingletonOnlyExceptTest()
    {
        Route::singleton('avatar', SingletonTestController::class)->creatable()->destroyable()->only(['show'])->except(['destroy']);

        $response = $this->get('/avatar');
        $this->assertEquals(200, $response->getStatusCode());

        $response = $this->get('/avatar/create');
        $this->assertEquals(404, $response->getStatusCode());

        $response = $this->post('/avatar');
        $this->assertEquals(405, $response->getStatusCode());

        $response = $this->get('/avatar/edit');
        $this->assertEquals(404, $response->getStatusCode());

        $response = $this->put('/avatar');
        $this->assertEquals(405, $response->getStatusCode());

        $response = $this->patch('/avatar');
        $this->assertEquals(405, $response->getStatusCode());

        $response = $this->delete('/avatar');
        $this->assertEquals(405, $response->getStatusCode());
    }

    public function testApiSingleton()
    {
        Route::apiSingleton('avatar', SingletonTestController::class);

        $response = $this->get('/avatar/create');
        $this->assertEquals(404, $response->getStatusCode());

        $response = $this->post('/avatar');
        $this->assertEquals(405, $response->getStatusCode());

        $this->assertSame('http://localhost/avatar', route('avatar.update'));
        $response = $this->put('/avatar');
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertSame('singleton update', $response->getContent());
    }

    public function testCreatableApiSingleton()
    {
        Route::apiSingleton('avatar', CreatableSingletonTestController::class)->creatable();

        $response = $this->get('/avatar/create');
        $this->assertEquals(404, $response->getStatusCode());

        $this->assertSame('http://localhost/avatar', route('avatar.store'));
        $response = $this->post('/avatar');
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertSame('singleton store', $response->getContent());

        $this->assertSame('http://localhost/avatar', route('avatar.update'));
        $response = $this->put('/avatar');
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertSame('singleton update', $response->getContent());
    }

    public function testCreatableApiSingletonOnly()
    {
        Route::apiSingleton('avatar', CreatableSingletonTestController::class)->creatable()->only(['create', 'store']);

        $response = $this->get('/avatar');
        $this->assertEquals(405, $response->getStatusCode());

        $response = $this->get('/avatar/create');
        $this->assertEquals(200, $response->getStatusCode());

        $response = $this->post('/avatar');
        $this->assertEquals(200, $response->getStatusCode());

        $response = $this->get('/avatar/edit');
        $this->assertEquals(404, $response->getStatusCode());

        $response = $this->put('/avatar');
        $this->assertEquals(405, $response->getStatusCode());

        $response = $this->patch('/avatar');
        $this->assertEquals(405, $response->getStatusCode());

        $response = $this->delete('/avatar');
        $this->assertEquals(405, $response->getStatusCode());
    }

    public function testCreatableApiSingletonExcept()
    {
        Route::apiSingleton('avatar', CreatableSingletonTestController::class)->creatable()->except(['create', 'store']);

        $response = $this->get('/avatar');
        $this->assertEquals(200, $response->getStatusCode());

        $response = $this->get('/avatar/create');
        $this->assertEquals(404, $response->getStatusCode());

        $response = $this->post('/avatar');
        $this->assertEquals(405, $response->getStatusCode());

        $response = $this->get('/avatar/edit');
        $this->assertEquals(404, $response->getStatusCode());

        $response = $this->put('/avatar');
        $this->assertEquals(200, $response->getStatusCode());

        $response = $this->patch('/avatar');
        $this->assertEquals(200, $response->getStatusCode());

        $response = $this->delete('/avatar');
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testDestroyableApiSingleton()
    {
        Route::apiSingleton('avatar', CreatableSingletonTestController::class)->destroyable();

        $this->assertSame('http://localhost/avatar', route('avatar.show'));
        $response = $this->get('/avatar');
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertSame('singleton show', $response->getContent());

        $this->assertSame('http://localhost/avatar', route('avatar.update'));
        $response = $this->put('/avatar');
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertSame('singleton update', $response->getContent());

        $this->assertSame('http://localhost/avatar', route('avatar.destroy'));
        $response = $this->delete('/avatar');
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertSame('singleton destroy', $response->getContent());
    }

    public function testDestroyableApiSingletonOnly()
    {
        Route::apiSingleton('avatar', CreatableSingletonTestController::class)->destroyable()->only(['destroy']);

        $response = $this->get('/avatar');
        $this->assertEquals(405, $response->getStatusCode());

        $response = $this->get('/avatar/create');
        $this->assertEquals(404, $response->getStatusCode());

        $response = $this->post('/avatar');
        $this->assertEquals(405, $response->getStatusCode());

        $response = $this->get('/avatar/edit');
        $this->assertEquals(404, $response->getStatusCode());

        $response = $this->put('/avatar');
        $this->assertEquals(405, $response->getStatusCode());

        $response = $this->patch('/avatar');
        $this->assertEquals(405, $response->getStatusCode());

        $response = $this->delete('/avatar');
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testDestroyableApiSingletonExcept()
    {
        Route::apiSingleton('avatar', CreatableSingletonTestController::class)->destroyable()->except(['destroy', 'show']);

        $response = $this->get('/avatar');
        $this->assertEquals(405, $response->getStatusCode());

        $response = $this->get('/avatar/create');
        $this->assertEquals(404, $response->getStatusCode());

        $response = $this->post('/avatar');
        $this->assertEquals(405, $response->getStatusCode());

        $response = $this->get('/avatar/edit');
        $this->assertEquals(404, $response->getStatusCode());

        $response = $this->put('/avatar');
        $this->assertEquals(200, $response->getStatusCode());

        $response = $this->patch('/avatar');
        $this->assertEquals(200, $response->getStatusCode());

        $response = $this->delete('/avatar');
        $this->assertEquals(405, $response->getStatusCode());
    }

    public function testCreatableDestroyableApiSingletonOnlyExceptTest()
    {
        Route::apiSingleton('avatar', CreatableSingletonTestController::class)->creatable()->destroyable()->only(['show'])->except(['destroy']);

        $response = $this->get('/avatar');
        $this->assertEquals(200, $response->getStatusCode());

        $response = $this->get('/avatar/create');
        $this->assertEquals(404, $response->getStatusCode());

        $response = $this->post('/avatar');
        $this->assertEquals(405, $response->getStatusCode());

        $response = $this->get('/avatar/edit');
        $this->assertEquals(404, $response->getStatusCode());

        $response = $this->put('/avatar');
        $this->assertEquals(405, $response->getStatusCode());

        $response = $this->patch('/avatar');
        $this->assertEquals(405, $response->getStatusCode());

        $response = $this->delete('/avatar');
        $this->assertEquals(405, $response->getStatusCode());
    }

    public function testSingletonOnly()
    {
        Route::singleton('avatar', SingletonTestController::class)->only('show');

        $response = $this->get('/avatar');
        $this->assertEquals(200, $response->getStatusCode());

        $response = $this->get('/avatar/edit');
        $this->assertEquals(404, $response->getStatusCode());

        $response = $this->put('/avatar');
        $this->assertEquals(405, $response->getStatusCode());

        $response = $this->patch('/avatar');
        $this->assertEquals(405, $response->getStatusCode());

        $response = $this->delete('/avatar');
        $this->assertEquals(405, $response->getStatusCode());
    }

    public function testSingletonExcept()
    {
        Route::singleton('avatar', SingletonTestController::class)->except('show');

        $response = $this->get('/avatar');
        $this->assertEquals(405, $response->getStatusCode());

        $response = $this->get('/avatar/edit');
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertSame('singleton edit', $response->getContent());

        $response = $this->put('/avatar');
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertSame('singleton update', $response->getContent());

        $response = $this->patch('/avatar');
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertSame('singleton update', $response->getContent());

        // $response = $this->delete('/avatar');
        // $this->assertEquals(200, $response->getStatusCode());
        // $this->assertSame('singleton destroy', $response->getContent());
    }

    public function testSingletonName()
    {
        Route::singleton('avatar', SingletonTestController::class)->name('show', 'foo.show');

        $this->assertSame('http://localhost/avatar', route('foo.show'));
    }

    public function testSingletonNames()
    {
        Route::singleton('avatar', SingletonTestController::class)->names(['show' => 'foo.show']);

        $this->assertSame('http://localhost/avatar', route('foo.show'));
    }

    public function testNestedSingleton()
    {
        Route::singleton('videos.thumbnail', NestedSingletonTestController::class);

        $response = $this->get('/videos/123/thumbnail');
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertSame('singleton show for 123', $response->getContent());

        $response = $this->get('/videos/123/thumbnail/edit');
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertSame('singleton edit for 123', $response->getContent());

        $response = $this->put('/videos/123/thumbnail');
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertSame('singleton update for 123', $response->getContent());

        $response = $this->patch('/videos/123/thumbnail');
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertSame('singleton update for 123', $response->getContent());

        $response = $this->delete('/videos/123/thumbnail');
        $this->assertEquals(405, $response->getStatusCode());
    }

    public function testCreatableNestedSingleton()
    {
        Route::singleton('videos.thumbnail', NestedSingletonTestController::class)->creatable();

        $response = $this->get('/videos/123/thumbnail');
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertSame('singleton show for 123', $response->getContent());

        $response = $this->get('/videos/123/thumbnail/edit');
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertSame('singleton edit for 123', $response->getContent());

        $response = $this->put('/videos/123/thumbnail');
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertSame('singleton update for 123', $response->getContent());

        $response = $this->patch('/videos/123/thumbnail');
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertSame('singleton update for 123', $response->getContent());

        $response = $this->delete('/videos/123/thumbnail');
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertSame('singleton destroy for 123', $response->getContent());
    }

    public function testDestroyableNestedSingleton()
    {
        Route::singleton('videos.thumbnail', NestedSingletonTestController::class)->destroyable();

        $response = $this->get('/videos/123/thumbnail');
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertSame('singleton show for 123', $response->getContent());

        $response = $this->get('/videos/123/thumbnail/edit');
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertSame('singleton edit for 123', $response->getContent());

        $response = $this->put('/videos/123/thumbnail');
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertSame('singleton update for 123', $response->getContent());

        $response = $this->patch('/videos/123/thumbnail');
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertSame('singleton update for 123', $response->getContent());

        $response = $this->delete('/videos/123/thumbnail');
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertSame('singleton destroy for 123', $response->getContent());
    }

    public function testNestedSingletonParameter()
    {
        Route::singleton('v.thumbnail', NestedSingletonTestController::class)->parameter('v', 'video');

        $response = $this->get('/v/123/thumbnail');

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertSame('singleton show for 123', $response->getContent());
    }

    public function testNestedSingletonParameters()
    {
        Route::singleton('v.thumbnail', NestedSingletonTestController::class)->parameters(['v' => 'video']);

        $response = $this->get('/v/123/thumbnail');

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertSame('singleton show for 123', $response->getContent());
    }

    public function testNestedSingletonWhere()
    {
        Route::singleton('videos.thumbnail', NestedSingletonTestController::class)->where(['video' => '[a-z]+']);

        $response = $this->get('/videos/123/thumbnail');

        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testPrefixedSingleton()
    {
        Route::singleton('/user/avatar', SingletonTestController::class);

        $response = $this->get('/user/avatar');

        $this->assertEquals(200, $response->getStatusCode());
    }
}
