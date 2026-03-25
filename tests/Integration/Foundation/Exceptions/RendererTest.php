<?php

namespace Illuminate\Tests\Integration\Foundation\Exceptions;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Foundation\ExceptionRenderer;
use Illuminate\Foundation\Exceptions\Renderer\Listener;
use Illuminate\Foundation\Exceptions\Renderer\Renderer;
use Illuminate\Foundation\Providers\FoundationServiceProvider;
use Mockery as m;
use Orchestra\Testbench\Attributes\WithConfig;
use Orchestra\Testbench\TestCase;
use RuntimeException;

class RendererTest extends TestCase
{
    protected function defineRoutes($router)
    {
        $router->get('failed', fn () => throw new RuntimeException('Bad route!'));
        $router->get('failed-with-previous', function () {
            throw new RuntimeException(
                'First exception', previous: new RuntimeException(
                    'Second exception', previous: new RuntimeException(
                        'Third exception'
                    )
                )
            );
        });
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

        $listener = m::mock(Listener::class);
        $listener->shouldReceive('registerListeners')->never();

        $this->app->instance(Listener::class, $listener);
        $this->app->instance(Dispatcher::class, m::mock(Dispatcher::class));

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

        $listener = m::mock(Listener::class);
        $listener->shouldReceive('registerListeners')->never();

        $this->app->instance(Listener::class, $listener);
        $this->app->instance(Dispatcher::class, m::mock(Dispatcher::class));

        $provider = $this->app->getProvider(FoundationServiceProvider::class);
        $provider->boot();
    }

    #[WithConfig('app.debug', true)]
    public function testItRegistersListenersWhenRendererNotBound()
    {
        $this->app->forgetInstance(ExceptionRenderer::class);
        $this->assertFalse($this->app->bound(ExceptionRenderer::class));

        $listener = m::mock(Listener::class);
        $listener->shouldReceive('registerListeners')->once();

        $this->app->instance(Listener::class, $listener);
        $this->app->instance(Dispatcher::class, m::mock(Dispatcher::class));

        $provider = $this->app->getProvider(FoundationServiceProvider::class);
        $provider->boot();
    }

    #[WithConfig('app.debug', true)]
    public function testItRendersPreviousExceptions()
    {
        $this->assertTrue($this->app->bound(Renderer::class));

        $this->get('/failed-with-previous')
            ->assertInternalServerError()
            ->assertSeeInOrder([
                'RuntimeException',
                'First exception',
                'Previous exceptions',
                'Second exception',
                'Third exception',
            ]);
    }

    #[WithConfig('app.debug', true)]
    public function testItExcludesDecorativeAsciiArtInNonBrowserContexts()
    {
        $this->get('/failed')
            ->assertInternalServerError()
            ->assertSee('RuntimeException')
            ->assertSee('Bad route!')
            ->assertDontSee('viewBox="0 0 1268 308"', false);
    }

    #[WithConfig('app.debug', true)]
    public function testItCanRenderExceptionAsMarkdown()
    {
        $response = $this->call('GET', '/failed', [], [], [], ['HTTP_ACCEPT' => 'text/markdown']);

        $response->assertInternalServerError();
        $response->assertHeader('Content-Type', 'text/markdown; charset=UTF-8');
        $response->assertSee('RuntimeException');
        $response->assertSee('Bad route!');
        $response->assertSee('## Stack Trace');
    }

    #[WithConfig('app.debug', true)]
    public function testItCanRenderExceptionAsMarkdownWithCharsetParameter()
    {
        $response = $this->call('GET', '/failed', [], [], [], ['HTTP_ACCEPT' => 'text/markdown; charset=utf-8']);

        $response->assertInternalServerError();
        $response->assertHeader('Content-Type', 'text/markdown; charset=UTF-8');
        $response->assertSee('## Stack Trace');
    }

    #[WithConfig('app.debug', true)]
    public function testJsonTakesPrecedenceOverMarkdown()
    {
        $response = $this->call('GET', '/failed', [], [], [], ['HTTP_ACCEPT' => 'application/json, text/markdown']);

        $response->assertInternalServerError();
        $response->assertHeader('Content-Type', 'application/json');
        $response->assertJsonStructure(['message', 'exception']);
    }

    #[WithConfig('app.debug', true)]
    public function testMarkdownTakesPrecedenceOverJson()
    {
        $response = $this->call('GET', '/failed', [], [], [], ['HTTP_ACCEPT' => 'text/markdown, application/json']);

        $response->assertInternalServerError();
        $response->assertHeader('Content-Type', 'text/markdown; charset=UTF-8');
        $response->assertSee('## Stack Trace');
    }

    #[WithConfig('app.debug', true)]
    public function testHtmlTakesPrecedenceOverMarkdown()
    {
        $response = $this->call('GET', '/failed', [], [], [], ['HTTP_ACCEPT' => 'text/html, text/markdown']);

        $response->assertInternalServerError();
        $this->assertStringContainsString('text/html', $response->headers->get('Content-Type'));
    }

    #[WithConfig('app.debug', false)]
    public function testMarkdownResponseDoesNotLeakDebugInfoWhenDebugOff()
    {
        $response = $this->call('GET', '/failed', [], [], [], ['HTTP_ACCEPT' => 'text/markdown']);

        $response->assertInternalServerError();
        $response->assertHeader('Content-Type', 'text/markdown; charset=UTF-8');
        $response->assertDontSee('## Stack Trace');
        $response->assertDontSee('Bad route!');
        $response->assertSee('Server Error');
    }

    #[WithConfig('app.debug', true)]
    public function testMarkdownFallsBackWhenCustomExceptionRendererRegistered()
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

        $response = $this->call('GET', '/failed', [], [], [], ['HTTP_ACCEPT' => 'text/markdown']);

        $response->assertInternalServerError();
        $response->assertHeader('Content-Type', 'text/markdown; charset=UTF-8');
        $response->assertDontSee('## Stack Trace');
        $response->assertSee('Server Error');
    }
}
