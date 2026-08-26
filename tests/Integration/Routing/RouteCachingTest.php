<?php

namespace Illuminate\Tests\Integration\Routing;

use Orchestra\Testbench\TestCase;

class RouteCachingTest extends TestCase
{
    public function testWildcardCatchAllRoutes()
    {
        $this->routes(dirname(__DIR__, 2).'/Routing/Fixtures/wildcard_catch_all_routes.php');

        $this->get('/foo')->assertSee('Regular route');
        $this->get('/bar')->assertSee('Wildcard route');
    }

    public function testRedirectRoutes()
    {
        $this->routes(dirname(__DIR__, 2).'/Routing/Fixtures/redirect_routes.php');

        $this->post('/foo/1')->assertRedirect('/foo/1/bar');
        $this->get('/foo/1/bar')->assertSee('Redirect response');
        $this->get('/foo/1')->assertRedirect('/foo/1/bar');
    }

    protected function routes(string $file)
    {
        $this->defineCacheRoutes(file_get_contents($file));
    }
}
