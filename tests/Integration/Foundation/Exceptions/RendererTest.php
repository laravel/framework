<?php

namespace Illuminate\Tests\Integration\Foundation\Exceptions;

use Illuminate\Foundation\Exceptions\Renderer\Renderer;
use Orchestra\Testbench\Attributes\WithConfig;
use Orchestra\Testbench\TestCase;
use RuntimeException;

class RendererTest extends TestCase
{
    protected function defineRoutes($router)
    {
        $router->get('failed', fn () => throw new RuntimeException('Bad route!'));
    }

    #[WithConfig('app.debug', true)]
    public function testItCanRenderExceptionPage()
    {
        $this->assertTrue($this->app->bound(Renderer::class));

        $this->get('/failed')
            ->assertInternalServerError()
            ->assertSee('RuntimeException')
            ->assertSee('Bad route!');
    }

    #[WithConfig('app.debug', false)]
    public function testItCanRenderExceptionPageUsingSymfonyIfRendererIsNotDefined()
    {
        config(['app.debug' => true]);

        $this->assertFalse($this->app->bound(Renderer::class));

        $this->get('/failed')
            ->assertInternalServerError()
            ->assertSee('RuntimeException')
            ->assertSee('Bad route!');
    }
}
