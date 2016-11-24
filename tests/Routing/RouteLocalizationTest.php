<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Events\Dispatcher;
use Illuminate\Container\Container;

class RouteLocalizationTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Application
     */
    protected $app;
    protected $url;

    public function setup(){
        $this->app = new Application();
    }

    public function testLocalizerMatchesRouteWithLocale()
    {
        $router = $this->getRouter();

        $router->get('foo', function () {
            return 'foo';
        })->middleware('localize');

        $this->app['config']->shouldReceive('get')->with('app.locales')->andReturn(['en', 'ar']);
        $this->app['config']->shouldReceive('get')->with('app.fallback_locale')->andReturn('en');

        $this->app['config']->shouldReceive('set')->with("app.locale", "ar");
        $this->app['translator']->shouldReceive('setLocale')->with('ar');

        $this->app['config']->shouldReceive('set')->with("app.locale", "en");
        $this->app['translator']->shouldReceive('setLocale')->with('en');

        $this->url->shouldReceive('formatPathUsing');

        $router->localize();

        $this->assertEquals('foo', $router->dispatch(Request::create('ar/foo', 'GET'))->getContent());
        $this->assertEquals('foo', $router->dispatch(Request::create('en/foo', 'GET'))->getContent());
    }

    /**
     * @expectedException Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function testLocalizerDoesntLocalizeRoutesWithoutTheMiddleware()
    {
        $router = $this->getRouter();

        $router->get('foo', function () {
            return 'foo';
        });

        $router->localize();

        $this->assertEquals('foo', $router->dispatch(Request::create('ar/foo', 'GET'))->getContent());
    }

    public function testLocalizerRedirectesToPathWithDefaultLocale()
    {
        $router = $this->getRouter();

        $redirector = Mockery::mock('Illuminate\Routing\Redirector');
        $redirector->shouldReceive('to')->once()->with('en/foo', 302, [], null);

        $this->app->bind('redirect', function () use ($redirector){
            return $redirector;
        });

        $this->app['config']->shouldReceive('get')->with('app.fallback_locale')->andReturn('en');
            $this->app['config']->shouldReceive('get')->with('app.locales')->andReturn(['en', 'ar']);

        $router->get('foo', function () {
            return 'foo';
        })->middleware('localize');

        $router->dispatch(Request::create('foo', 'GET'));
    }

    protected function getRouter()
    {
        $this->url = Mockery::mock('Illuminate\Contracts\Routing\UrlGenerator');

        $this->app['config'] = Mockery::mock('Illuminate\Contracts\Config\Repository');

        $this->app['translator'] = Mockery::mock('Illuminate\Translation\Translator');

        $this->app->bind('Illuminate\Contracts\Routing\UrlGenerator', function () {
            return $this->url;
        });

        $router = new Router(new Dispatcher, $this->app);

        $router->middleware('localize', 'Illuminate\Routing\Middleware\Localize');

        return $router;
    }
}
