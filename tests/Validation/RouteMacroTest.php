<?php

namespace Illuminate\Tests\Validation;

use Illuminate\Container\Container;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Facade;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\ValidationServiceProvider;
use PHPUnit\Framework\TestCase;

class RouteMacroTest extends TestCase
{
    protected $container;
    protected $trans;

    protected function setUp(): void
    {
        $this->container = new Container;
        $this->trans = new Translator(
            new ArrayLoader, 'en'
        );
        $this->container->instance('translator', $this->trans);

        // Set up Facade
        Facade::setFacadeApplication($this->container);

        // Register the validation service provider
        $provider = new ValidationServiceProvider($this->container);
        $provider->register();
        $provider->boot();

        Container::setInstance($this->container);
    }

    protected function tearDown(): void
    {
        Container::setInstance();
        Facade::clearResolvedInstances();
    }    /**
     * Test that route macros are registered and callable.
     */
    public function testRouteMacrosAreRegistered()
    {
        // Check if the macros exist
        $this->assertTrue(Route::hasMacro('validateRequestBy'));
        $this->assertTrue(Route::hasMacro('validateRouteParams'));
        $this->assertTrue(Route::hasMacro('validateQuery'));
    }

    /**
     * Test validateRequestBy macro with JSON file path.
     */
    public function testValidateRequestByMacroWithJsonFile()
    {
        // Create a basic route instance manually
        $route = new Route(['GET'], '/users/{id}', function ($id) {
            return ['id' => $id];
        });

        // Test that the macro can be called with a file path
        $result = $route->validateRequestBy('user-validation');

        $this->assertInstanceOf(Route::class, $result);

        // Check that middleware was added
        $middleware = $route->gatherMiddleware();
        $this->assertNotEmpty($middleware);
    }

    /**
     * Test validateRequestBy macro throws exception with array input.
     */
    public function testValidateRequestByMacroThrowsExceptionWithArray()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('validateRequestBy() only accepts file paths to JSON files. Use validateQuery() for array rules.');

        $route = new Route(['GET'], '/users/{id}', function ($id) {
            return ['id' => $id];
        });

        $route->validateRequestBy([
            'id' => 'required|integer|min:1'
        ]);
    }

    /**
     * Test validateRouteParams macro.
     */
    public function testValidateRouteParamsMacro()
    {
        $route = new Route(['GET'], '/users/{id}/posts/{postId}', function ($id, $postId) {
            return ['userId' => $id, 'postId' => $postId];
        });

        $result = $route->validateRouteParams([
            'id' => 'required|integer',
            'postId' => 'required|integer'
        ]);

        $this->assertInstanceOf(Route::class, $result);

        // Check that middleware was added
        $middleware = $route->gatherMiddleware();
        $this->assertNotEmpty($middleware);
    }

    /**
     * Test validateQuery macro with array input.
     */
    public function testValidateQueryMacro()
    {
        $route = new Route(['GET'], '/search', function () {
            return ['results' => []];
        });

        $result = $route->validateQuery([
            'q' => 'required|string|min:3',
            'page' => 'integer|min:1'
        ]);

        $this->assertInstanceOf(Route::class, $result);

        // Check that middleware was added
        $middleware = $route->gatherMiddleware();
        $this->assertNotEmpty($middleware);
    }

    /**
     * Test validateQuery macro throws exception with file path input.
     */
    public function testValidateQueryMacroThrowsExceptionWithFilePath()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('validateQuery() only accepts arrays of validation rules. Use validateRequestBy() for JSON file paths.');

        $route = new Route(['GET'], '/search', function () {
            return ['results' => []];
        });

        $route->validateQuery('search-validation');
    }
}
