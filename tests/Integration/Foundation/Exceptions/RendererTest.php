<?php

namespace Illuminate\Tests\Integration\Foundation\Exceptions;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Foundation\ExceptionRenderer;
use Illuminate\Foundation\Exceptions\Renderer\Listener;
use Illuminate\Foundation\Exceptions\Renderer\Renderer;
use Illuminate\Foundation\Providers\FoundationServiceProvider;
use Mockery;
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

    #[WithConfig('app.debug', true)]
    public function testItCanRenderExceptionPageWithRendererWhenDebugEnabled()
    {
        $this->app->singleton(ExceptionRenderer::class, function () {
            return new class() implements ExceptionRenderer
            {
                public function render($throwable)
                {
                    return response('Custom Exception Renderer: '.$throwable->getMessage(), 500);
                }
            };
        });

        $this->assertTrue($this->app->bound(ExceptionRenderer::class));

        $this->get('/failed')
            ->assertInternalServerError()
            ->assertSee('Custom Exception Renderer: Bad route!');
    }

    #[WithConfig('app.debug', false)]
    public function testItDoesNotRenderExceptionPageWithRendererWhenDebugDisabled()
    {
        $this->app->singleton(ExceptionRenderer::class, function () {
            return new class() implements ExceptionRenderer
            {
                public function render($throwable)
                {
                    return response('Custom Exception Renderer: '.$throwable->getMessage(), 500);
                }
            };
        });

        $this->assertTrue($this->app->bound(ExceptionRenderer::class));

        $this->get('/failed')
            ->assertInternalServerError()
            ->assertDontSee('Custom Exception Renderer: Bad route!');
    }

    #[WithConfig('app.debug', false)]
    public function testItDoesNotRegisterListenersWhenDebugDisabled()
    {
        $this->app->forgetInstance(ExceptionRenderer::class);
        $this->assertFalse($this->app->bound(ExceptionRenderer::class));

        $listener = Mockery::mock(Listener::class);
        $listener->shouldReceive('registerListeners')->never();

        $this->app->instance(Listener::class, $listener);
        $this->app->instance(Dispatcher::class, Mockery::mock(Dispatcher::class));

        $provider = $this->app->getProvider(FoundationServiceProvider::class);
        $provider->boot();
    }

    #[WithConfig('app.debug', true)]
    public function testItDoesNotRegisterListenersWhenRendererBound()
    {
        $this->app->singleton(ExceptionRenderer::class, function () {
            return new class() implements ExceptionRenderer
            {
                public function render($throwable)
                {
                    return response('Custom Exception Renderer: '.$throwable->getMessage(), 500);
                }
            };
        });

        $this->assertTrue($this->app->bound(ExceptionRenderer::class));

        $listener = Mockery::mock(Listener::class);
        $listener->shouldReceive('registerListeners')->never();

        $this->app->instance(Listener::class, $listener);
        $this->app->instance(Dispatcher::class, Mockery::mock(Dispatcher::class));

        $provider = $this->app->getProvider(FoundationServiceProvider::class);
        $provider->boot();
    }

    #[WithConfig('app.debug', true)]
    public function testItRegistersListenersWhenRendererNotBound()
    {
        $this->app->forgetInstance(ExceptionRenderer::class);
        $this->assertFalse($this->app->bound(ExceptionRenderer::class));

        $listener = Mockery::mock(Listener::class);
        $listener->shouldReceive('registerListeners')->once();

        $this->app->instance(Listener::class, $listener);
        $this->app->instance(Dispatcher::class, Mockery::mock(Dispatcher::class));

        $provider = $this->app->getProvider(FoundationServiceProvider::class);
        $provider->boot();
    }
}
