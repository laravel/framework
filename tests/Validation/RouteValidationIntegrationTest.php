<?php

namespace Illuminate\Tests\Validation;

use Illuminate\Container\Container;
use Illuminate\Events\Dispatcher;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\CallableDispatcher;
use Illuminate\Routing\Contracts\CallableDispatcher as CallableDispatcherContract;
use Illuminate\Routing\Contracts\ControllerDispatcher as ControllerDispatcherContract;
use Illuminate\Routing\ControllerDispatcher;
use Illuminate\Routing\Redirector;
use Illuminate\Routing\ResponseFactory;
use Illuminate\Routing\Route;
use Illuminate\Routing\RouteCollection;
use Illuminate\Routing\Router;
use Illuminate\Routing\UrlGenerator;
use Illuminate\Support\Facades\Facade;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\ValidationServiceProvider;
use Illuminate\View\Engines\EngineResolver;
use Illuminate\View\Engines\PhpEngine;
use Illuminate\View\Factory as ViewFactory;
use Illuminate\View\FileViewFinder;
use PHPUnit\Framework\TestCase;

class RouteValidationIntegrationTest extends TestCase
{
    protected $container;
    protected $trans;
    protected $router;
    protected $tempDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->container = new Container;
        $this->trans = new Translator(
            new ArrayLoader, 'en'
        );
        $this->container->instance('translator', $this->trans);

        // Set up temporary directory for test files
        $this->tempDir = sys_get_temp_dir().'/laravel_validation_test_'.uniqid();
        if (! is_dir($this->tempDir)) {
            mkdir($this->tempDir, 0755, true);
        }

        // Set up Facade
        Facade::setFacadeApplication($this->container);

        // Set up ViewFactory dependencies
        $filesystem = new Filesystem;
        $this->container->instance('files', $filesystem);

        $engineResolver = new EngineResolver;
        $engineResolver->register('php', function () use ($filesystem) {
            return new PhpEngine($filesystem);
        });

        $viewFinder = new FileViewFinder($filesystem, []);
        $events = new Dispatcher($this->container);

        $viewFactory = new ViewFactory($engineResolver, $viewFinder, $events);
        $this->container->instance('view', $viewFactory);
        $this->container->instance('Illuminate\Contracts\View\Factory', $viewFactory);

        // Set up Router
        $this->router = new Router(new Dispatcher, $this->container);
        $this->container->instance('router', $this->router);

        // Set up UrlGenerator
        $routes = new RouteCollection;
        $this->container->instance('routes', $routes);

        $urlGenerator = new UrlGenerator($routes, Request::create('/'));
        $this->container->instance('url', $urlGenerator);

        // Set up Redirector
        $redirector = new Redirector($urlGenerator);
        $this->container->instance('redirect', $redirector);

        // Register response factory with proper dependencies
        $this->container->singleton('Illuminate\Contracts\Routing\ResponseFactory', function ($app) {
            return new ResponseFactory($app['view'], $app['redirect']);
        });

        $this->container->bind(ControllerDispatcherContract::class, fn ($app) => new ControllerDispatcher($app));
        $this->container->bind(CallableDispatcherContract::class, fn ($app) => new CallableDispatcher($app));

        // Register the validation service provider
        $provider = new ValidationServiceProvider($this->container);
        $provider->register();
        $provider->boot();

        Container::setInstance($this->container);
    }

    protected function tearDown(): void
    {
        // Clean up temp files and directories recursively
        if (is_dir($this->tempDir)) {
            $this->removeDirectory($this->tempDir);
        }

        // Clean up global singletons to prevent test contamination
        Container::setInstance();
        Facade::clearResolvedInstances();
        AliasLoader::setInstance(null);
    }

    /**
     * Recursively remove a directory and all its contents.
     */
    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $items = scandir($dir);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $dir . DIRECTORY_SEPARATOR . $item;
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                unlink($path);
            }
        }

        rmdir($dir);
    }

    /**
     * Create a test schema file for testing.
     */
    protected function createTestSchemaFile($rules, $filename = 'test-validation.json')
    {
        $path = $this->tempDir.'/resources/validation';

        if (! is_dir($path)) {
            mkdir($path, 0755, true);
        }

        $fullPath = $path.'/'.$filename;
        file_put_contents($fullPath, json_encode($rules));

        // Return the full absolute path for the ValidationSchemaLoader
        return $fullPath;
    }

    /**
     * Handle request through the router with proper error handling.
     */
    protected function handleRequest(Request $request)
    {
        try {
            return $this->router->dispatch($request);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            // Create a proper error response for other exceptions
            return new Response('Internal Server Error: '.$e->getMessage(), 500);
        }
    }

    /**
     * Test route parameter validation with inline rules.
     */
    public function testRouteParamValidationWithInlineRules()
    {
        $this->router->get('/users/{id}', function ($id) {
            return response()->json(['id' => $id]);
        })->validateRouteParams([
            'id' => 'required|integer|min:1',
        ]);

        // Test valid request
        $request = Request::create('/users/123', 'GET');
        $request->setRouteResolver(function () use ($request) {
            return $this->router->getRoutes()->match($request);
        });

        $response = $this->handleRequest($request);
        $this->assertEquals(200, $response->getStatusCode());

        // Test invalid request (non-integer)
        $request = Request::create('/users/abc', 'GET');
        $request->setRouteResolver(function () use ($request) {
            return $this->router->getRoutes()->match($request);
        });

        try {
            $response = $this->handleRequest($request);
            $this->fail('Expected ValidationException was not thrown');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('id', $e->errors());
        }
    }

    /**
     * Test query parameter validation with inline rules.
     */
    public function testQueryParamValidationWithInlineRules()
    {
        $this->router->get('/search', function () {
            return response()->json(['results' => []]);
        })->validateQuery([
            'q' => 'required|string|min:3',
            'page' => 'integer|min:1',
        ]);

        // Test valid request
        $request = Request::create('/search?q=test&page=1', 'GET');
        $request->setRouteResolver(function () use ($request) {
            return $this->router->getRoutes()->match($request);
        });

        $response = $this->handleRequest($request);
        $this->assertEquals(200, $response->getStatusCode());

        // Test invalid request (missing required query param)
        $request = Request::create('/search', 'GET');
        $request->setRouteResolver(function () use ($request) {
            return $this->router->getRoutes()->match($request);
        });

        try {
            $response = $this->handleRequest($request);
            $this->fail('Expected ValidationException was not thrown');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('q', $e->errors());
        }
    }

    /**
     * Test request validation with file-based schema.
     */
    public function testRequestValidationWithFileSchema()
    {
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|email',
        ];

        $schemaFile = $this->createTestSchemaFile($rules, 'user-validation.json');

        $this->router->post('/users', function () {
            return response()->json(['message' => 'User created']);
        })->validateRequestBy($schemaFile, true); // validateAll = true

        // Test valid request
        $request = Request::create('/users', 'POST', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);
        $request->setRouteResolver(function () use ($request) {
            return $this->router->getRoutes()->match($request);
        });

        $response = $this->handleRequest($request);
        $this->assertEquals(200, $response->getStatusCode());

        // Test invalid request (invalid email)
        $request = Request::create('/users', 'POST', [
            'name' => 'John Doe',
            'email' => 'invalid-email',
        ]);
        $request->setRouteResolver(function () use ($request) {
            return $this->router->getRoutes()->match($request);
        });

        try {
            $response = $this->handleRequest($request);
            $this->fail('Expected ValidationException was not thrown');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('email', $e->errors());
        }
    }

    /**
     * Test flat Laravel validation rules format using JSON file.
     */
    public function testFlatValidationRulesFormat()
    {
        $rules = [
            'id' => 'required|integer',
            'profile.name' => 'required|string',
            'profile.settings.*.key' => 'required|string',
            'profile.settings.*.value' => 'required',
        ];

        $schemaFile = $this->createTestSchemaFile($rules, 'profile-validation.json');

        $this->router->post('/users/{id}/profile', function ($id) {
            return response()->json(['message' => 'Profile updated']);
        })->validateRequestBy($schemaFile, true);

        // Test valid request with nested data
        $request = Request::create('/users/123/profile', 'POST', [
            'id' => 123,
            'profile' => [
                'name' => 'John Doe',
                'settings' => [
                    ['key' => 'theme', 'value' => 'dark'],
                    ['key' => 'language', 'value' => 'en'],
                ],
            ],
        ]);
        $request->setRouteResolver(function () use ($request) {
            return $this->router->getRoutes()->match($request);
        });

        $response = $this->handleRequest($request);
        $this->assertEquals(200, $response->getStatusCode());

        // Test invalid request (missing nested required field)
        $request = Request::create('/users/123/profile', 'POST', [
            'id' => 123,
            'profile' => [
                'settings' => [
                    ['key' => 'theme'], // missing value
                ],
            ],
        ]);
        $request->setRouteResolver(function () use ($request) {
            return $this->router->getRoutes()->match($request);
        });

        try {
            $response = $this->handleRequest($request);
            $this->fail('Expected ValidationException was not thrown');
        } catch (ValidationException $e) {
            $errors = $e->errors();
            $this->assertTrue(
                isset($errors['profile.name']) ||
                isset($errors['profile.settings.0.value']),
                'Expected validation errors for nested fields'
            );
        }
    }

    /**
     * Test validateRequestBy throws exception when passed an array.
     */
    public function testValidateRequestByThrowsExceptionWithArray()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('validateRequestBy() only accepts file paths to JSON files. Use validateQuery() for array rules.');

        $this->router->post('/users', function () {
            return response()->json(['message' => 'User created']);
        })->validateRequestBy([
            'name' => 'required|string|max:255',
            'email' => 'required|email',
        ]);
    }

    /**
     * Test validateQuery throws exception when passed a file path.
     */
    public function testValidateQueryThrowsExceptionWithFilePath()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('validateQuery() only accepts arrays of validation rules. Use validateRequestBy() for JSON file paths.');

        $this->router->get('/search', function () {
            return response()->json(['results' => []]);
        })->validateQuery('search-validation');
    }
}
