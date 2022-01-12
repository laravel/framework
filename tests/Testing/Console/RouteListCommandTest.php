<?php

namespace Illuminate\Tests\Testing;

use Illuminate\Contracts\Routing\Registrar;
use Illuminate\Foundation\Auth\User;
use Illuminate\Foundation\Console\RouteListCommand;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Facade;
use Orchestra\Testbench\TestCase;

class RouteListCommandTest extends TestCase
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

        RouteListCommand::resolveTerminalWidthUsing(function () {
            return 70;
        });
    }

    public function testDisplayRoutesForCli()
    {
        $this->router->get('closure', function () {
            return new RedirectResponse($this->urlGenerator->signedRoute('signed-route'));
        });

        $this->router->get('controller-method/{user}', [FooController::class, 'show']);
        $this->router->post('controller-invokable', FooController::class);
        $this->router->domain('{account}.example.com')->group(function () {
            $this->router->get('user/{id}', function ($account, $id) {
                //
            })->name('user.show')->middleware('web');
        });

        $this->artisan(RouteListCommand::class)
            ->assertSuccessful()
            ->expectsOutput('')
            ->expectsOutput('  GET|HEAD   closure ............................................... ')
            ->expectsOutput('  POST       controller-invokable Illuminate\Tests\Testing\FooContr…')
            ->expectsOutput('  GET|HEAD   controller-method/{user} Illuminate\Tests\Testing\FooC…')
            ->expectsOutput('  GET|HEAD   {account}.example.com/user/{id} ............. user.show')
            ->expectsOutput('');
    }

    public function testDisplayRoutesForCliInVerboseMode()
    {
        $this->router->get('closure', function () {
            return new RedirectResponse($this->urlGenerator->signedRoute('signed-route'));
        });

        $this->router->get('controller-method/{user}', [FooController::class, 'show']);
        $this->router->post('controller-invokable', FooController::class);
        $this->router->domain('{account}.example.com')->group(function () {
            $this->router->get('user/{id}', function ($account, $id) {
                //
            })->name('user.show')->middleware('web');
        });
        $this->router->view('view', 'welcome');
        $this->router->redirect('about', 'about-us');

        $this->artisan(RouteListCommand::class, ['-v' => true])
            ->assertSuccessful()
            ->expectsOutput('')
            ->expectsOutput('  ANY        about ........... Illuminate\\Routing\\RedirectController')
            ->expectsOutput('             └ about-us 302')
            ->expectsOutput('  GET|HEAD   closure ............................................... ')
            ->expectsOutput('             └ ' . __FILE__ . ':62')
            ->expectsOutput('  POST       controller-invokable Illuminate\\Tests\\Testing\\FooController')
            ->expectsOutput('             └ ' . __FILE__ . ':112')
            ->expectsOutput('  GET|HEAD   controller-method/{user} Illuminate\\Tests\\Testing\\FooController@show')
            ->expectsOutput('             └ ' . __FILE__ . ':107')
            ->expectsOutput('  GET|HEAD   {account}.example.com/user/{id} ............. user.show')
            ->expectsOutput('             ⇂ web')
            ->expectsOutput('             └ ' . __FILE__ . ':69')
            ->expectsOutput('  GET|HEAD   view .................................................. ')
            ->expectsOutput('             └ resources/views/welcome.blade.php')
            ->expectsOutput('');
    }

    public function tearDown(): void
    {
        parent::tearDown();

        Facade::setFacadeApplication(null);

        RouteListCommand::resolveTerminalWidthUsing(null);
    }
}

class FooController extends Controller
{
    public function show(User $user)
    {
        // ..
    }

    public function __invoke()
    {
        // ..
    }
}
