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
        $this->get('/foo/1')->assertSee('GET response');
    }

    public function testDifferentMethodsForOneUrl()
    {
        $this->routes(__DIR__.'/Fixtures/conflicty_routes.php');

        $this->delete('/url')->assertSee('response from any');
        $this->patch('/url')->assertSee('response from any');

        $this->get('/url')->assertSee('2');
        $this->get('/_slug_')->assertSee('sluggish (-_-)zzz');

        $this->post('/url')->assertSee('1');
    }

    protected function routes(string $file)
    {
        $this->defineCacheRoutes(file_get_contents($file));
    }
}
