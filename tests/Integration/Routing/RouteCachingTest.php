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

    public function testCachedRouteFileStoresASerializedPayload()
    {
        $this->routes(__DIR__.'/Fixtures/wildcard_catch_all_routes.php');

        $contents = file_get_contents($this->app->getCachedRoutesPath());

        // The route cache file remains a require()-able PHP file so that it stays
        // compatible with previously generated caches and any tooling that loads
        // it directly...
        $this->assertStringContainsString("app('router')->setCompiledRoutes(", $contents);

        // ...but the compiled route data is now stored as a serialized string that
        // is rehydrated with unserialize() instead of being dumped into the file as
        // a large var_export()ed PHP array.
        $this->assertStringContainsString('unserialize(', $contents);

        // The cached routes still resolve correctly once the file is loaded.
        $this->get('/foo')->assertSee('Regular route');
    }

    protected function routes(string $file)
    {
        $this->defineCacheRoutes(file_get_contents($file));
    }
}
