<?php

namespace Illuminate\Tests\Integration\Routing;

use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Orchestra\Testbench\TestCase;

class WithoutViewComponentTest extends TestCase
{

    protected function setUp(): void
    {
        parent::setUp();
        if (
            $this->app->hasBeenBootstrapped()
            && $this->app->has(ResponseFactory::class)
            && $this->app->has('view')
        ) {
            $this->app->forgetInstance('view');
            $this->app->forgetInstance(ResponseFactory::class);
            $this->app->forgetInstance('view');
        }
    }

    public function testSignedMiddlewareWithInvalidUrl()
    {
        $url = '/foo/bar/baz';
        Route::view($url, 'view', ['foo' => 'baz']);
        View::addLocation(__DIR__.'/Fixtures');

        $response = $this->get($url);

        $this->assertStringContainsString('Test baz', $response->getContent());
        $response->assertStatus(200);
    }
}
