<?php

namespace Illuminate\Tests\Testing;

use Illuminate\Contracts\Routing\Registrar;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\UrlGenerator;
use Illuminate\Support\Facades\Facade;
use Orchestra\Testbench\TestCase;

class AssertRedirectToRouteTest extends TestCase
{
    /**
     * @var \Illuminate\Contracts\Routing\Registrar
     */
    private $router;

    /**
     * @var \Illuminate\Routing\UrlGenerator
     */
    private $urlGenerator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->router = $this->app->make(Registrar::class);

        $this->router
            ->get('named-route')
            ->name('named-route');

        $this->router
            ->get('named-route-with-param/{param}')
            ->name('named-route-with-param');

        $this->urlGenerator = $this->app->make(UrlGenerator::class);
    }

    public function testAssertRedirectToRouteWithRouteName()
    {
        $this->router->get('test-route', function () {
            return new RedirectResponse($this->urlGenerator->route('named-route'));
        });

        $this->get('test-route')
            ->assertRedirectToRoute('named-route');
    }

    public function testAssertRedirectToRouteWithRouteNameAndParams()
    {
        $this->router->get('test-route', function () {
            return new RedirectResponse($this->urlGenerator->route('named-route-with-param', 'hello'));
        });

        $this->router->get('test-route-with-extra-param', function () {
            return new RedirectResponse($this->urlGenerator->route('named-route-with-param', [
                'param' => 'foo',
                'extra' => 'another',
            ]));
        });

        $this->get('test-route')
            ->assertRedirectToRoute('named-route-with-param', 'hello');

        $this->get('test-route-with-extra-param')
            ->assertRedirectToRoute('named-route-with-param', [
                'param' => 'foo',
                'extra' => 'another',
            ]);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        Facade::setFacadeApplication(null);
    }
}
