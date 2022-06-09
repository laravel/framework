<?php

namespace Illuminate\Tests\Routing;

use Illuminate\Container\Container;
use Illuminate\Contracts\Routing\Registrar;
use Illuminate\Events\Dispatcher;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class RouteLockInProductionTest extends TestCase
{
    public function testCanRegisterLockInLocal()
    {
        $this->setApplicationEnv('local');

        $router = $this->getRouter();
        $router->get('run-artisan', function () {
            return 'done';
        })->lockInProduction();

        $this->assertStringContainsString('done', $router->dispatch(Request::create('run-artisan', 'GET'))->getContent());
        $this->assertEquals(200, $router->dispatch(Request::create('run-artisan', 'GET'))->getStatusCode());
    }

    public function testCanRegisterLockInProduction()
    {
        $this->expectException(NotFoundHttpException::class);

        $this->setApplicationEnv('production');

        $router = $this->getRouter();
        $router->get('run-artisan', function () {
            return 'done';
        })->lockInProduction();

        $this->assertStringContainsString('Not Found', $router->dispatch(Request::create('run-artisan', 'GET'))->getContent());
        $this->assertEquals(404, $router->dispatch(Request::create('run-artisan', 'GET'))->getStatusCode());
    }

    /**
     * Set environment to Application.
     *
     * @param  string  $environment
     * @return void
     */
    public function setApplicationEnv($environment)
    {
        $local = new Application;
        $local['env'] = $environment;
    }

    protected function getRouter()
    {
        $container = new Container;

        $router = new Router(new Dispatcher, $container);

        $container->singleton(Registrar::class, function () use ($router) {
            return $router;
        });

        return $router;
    }
}
