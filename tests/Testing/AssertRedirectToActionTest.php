<?php

namespace Illuminate\Tests\Testing;

use Illuminate\Contracts\Routing\Registrar;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\Routing\UrlGenerator;
use Illuminate\Support\Facades\Facade;
use Orchestra\Testbench\TestCase;

class AssertRedirectToActionTest extends TestCase
{
    /**
     * @var \Illuminate\Contracts\Routing\Registrar
     */
    private $router;

    /**
     * @var \Illuminate\Routing\UrlGenerator
     */
    public $urlGenerator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->router = $this->app->make(Registrar::class);

        $this->router->get('controller/index', [TestActionController::class, 'index']);
        $this->router->get('controller/show/{id}', [TestActionController::class, 'show']);

        $this->router->get('redirect-to-index', function () {
            return new RedirectResponse($this->urlGenerator->action([TestActionController::class, 'index']));
        });

        $this->router->get('redirect-to-show', function () {
            return new RedirectResponse($this->urlGenerator->action([TestActionController::class, 'show'], ['id' => 123]));
        });

        $this->urlGenerator = $this->app->make(UrlGenerator::class);
    }

    public function testAssertRedirectToActionWithoutParameters(): void
    {
        $this->get('redirect-to-index')
            ->assertRedirectToAction([TestActionController::class, 'index']);
    }

    public function testAssertRedirectToActionWithParameters(): void
    {
        $this->get('redirect-to-show')
            ->assertRedirectToAction([TestActionController::class, 'show'], ['id' => 123]);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        Facade::setFacadeApplication(null);
    }
}

class TestActionController extends Controller
{
    public function index()
    {
        return 'ok';
    }

    public function show($id)
    {
        return "id: $id";
    }
}
