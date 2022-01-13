<?php

namespace Illuminate\Tests\Testing\Console;

use Illuminate\Contracts\Routing\Registrar;
use Illuminate\Foundation\Console\RouteListCommand;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Facade;
use function Illuminate\Tests\Testing\Console\Fixtures\accountId;
use Illuminate\Tests\Testing\Console\Fixtures\FooController;
use function Illuminate\Tests\Testing\Console\Fixtures\signedRoute;
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
            ->expectsOutput('  POST       controller-invokable Illuminate\Tests\Testing\Console\…')
            ->expectsOutput('  GET|HEAD   controller-method/{user} Illuminate\Tests\Testing\Cons…')
            ->expectsOutput('  GET|HEAD   {account}.example.com/user/{id} ............. user.show')
            ->expectsOutput('');
    }

    public function testDisplayRoutesForCliInVerboseMode()
    {
        $ds = DIRECTORY_SEPARATOR;
        $functions = __DIR__.$ds.'Fixtures'.$ds.'functions.php';
        $controller = __DIR__.$ds.'Fixtures'.$ds.'FooController.php';
        require_once $functions;

        $this->router->get('closure', signedRoute());

        $this->router->get('controller-method/{user}', [FooController::class, 'show']);
        $this->router->post('controller-invokable', FooController::class);
        $this->router->domain('{account}.example.com')->group(function () {
            $this->router->get('user/{id}', accountId())->name('user.show')->middleware('web');
        });
        $this->router->view('view', 'welcome');
        $this->router->redirect('about', 'about-us');

        $this->artisan(RouteListCommand::class, ['-v' => true])
            ->assertSuccessful()
            ->expectsOutput('')
            ->expectsOutput('  ANY        about ........... Illuminate\\Routing\\RedirectController')
            ->expectsOutput('             └ about-us 302')
            ->expectsOutput('  GET|HEAD   closure ............................................... ')
            ->expectsOutput('             └ '.$functions.':9')
            ->expectsOutput('  POST       controller-invokable Illuminate\\Tests\\Testing\\Console\\Fixtures\\FooController')
            ->expectsOutput('             └ '.$controller.':15')
            ->expectsOutput('  GET|HEAD   controller-method/{user} Illuminate\\Tests\\Testing\\Console\\Fixtures\\FooController@show')
            ->expectsOutput('             └ '.$controller.':10')
            ->expectsOutput('  GET|HEAD   {account}.example.com/user/{id} ............. user.show')
            ->expectsOutput('             ⇂ web')
            ->expectsOutput('             └ '.$functions.':16')
            ->expectsOutput('  GET|HEAD   view .................................................. ')
            ->expectsOutput('             └ resources'.$ds.'views'.$ds.'welcome.blade.php')
            ->expectsOutput('');
    }

    public function tearDown(): void
    {
        parent::tearDown();

        Facade::setFacadeApplication(null);

        RouteListCommand::resolveTerminalWidthUsing(null);
    }
}
