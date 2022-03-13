<?php

namespace Illuminate\Tests\Integration\Routing;

use Orchestra\Testbench\TestCase;

class RouteCachingTest extends TestCase
{
    public function testWildcardCatchAllRoutes()
    {
        $this->routes(__DIR__.'/Fixtures/wildcard_catch_all_routes.php');

        $this->get('/foo')->assertSee('Regular route');
        $this->get('/bar')->assertSee('Wildcard route');
    }

    public function testRedirectRoutes()
    {
        $this->routes(__DIR__.'/Fixtures/redirect_routes.php');

        $this->post('/foo/1')->assertRedirect('/foo/1/bar');
        $this->get('/foo/1/bar')->assertSee('Redirect response');
        $this->get('/foo/1')->assertRedirect('/foo/1/bar');
    }

    public function testCallbackRoutes()
    {
        $this->routes(__DIR__.'/Fixtures/callback_routes.php');

        $this->get('/foo')->assertNotFound();
        $this->get('/bar')->assertSee('Wildcard matched route');
        $this->get('/baz')->assertSee('Wildcard matched route');

        $this->get('/foo/bar')->assertNotFound();
        $this->get('/foo/baz')->assertSee('Regular matched route');
    }

    protected function routes(string $file)
    {
        $this->defineCacheRoutes(file_get_contents($file));
    }
}
