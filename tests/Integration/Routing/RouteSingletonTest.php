<?php

namespace Illuminate\Tests\Integration\Routing;

use Illuminate\Support\Facades\Route;
use Illuminate\Tests\Integration\Routing\Fixtures\NestedSingletonTestController;
use Illuminate\Tests\Integration\Routing\Fixtures\SingletonTestController;
use Orchestra\Testbench\TestCase;

class RouteSingletonTest extends TestCase
{
    public function testSingletonDefaults()
    {
        Route::singleton('avatar', SingletonTestController::class);

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

        $this->assertSame('http://localhost/avatar', route('avatar.destroy'));
        $response = $this->delete('/avatar');
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertSame('singleton destroy', $response->getContent());
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
        $response = $this->patch('/avatar');
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertSame('singleton update', $response->getContent());

        $response = $this->delete('/avatar');
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertSame('singleton destroy', $response->getContent());
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
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertSame('singleton destroy for 123', $response->getContent());
    }

    public function testNestedSingletonParameter()
    {
        Route::singleton('things.thumbnail', NestedSingletonTestController::class)->parameter('thing', 'video');

        $response = $this->get('/things/123/thumbnail');
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertSame('singleton show for 123', $response->getContent());
    }

    public function testNestedSingletonParameters()
    {
        Route::singleton('things.thumbnail', NestedSingletonTestController::class)->parameters(['thing' => 'video']);

        $response = $this->get('/things/123/thumbnail');
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
