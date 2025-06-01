<?php

namespace Illuminate\Tests\Validation;

use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route as RouteFacade;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class RouteValidationIntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Ensure the validation service provider is loaded
        $this->app->register(\Illuminate\Validation\ValidationServiceProvider::class);
    }

    /**
     * Create a test schema file for testing.
     */
    protected function createTestSchemaFile($rules, $filename = 'test-validation.json')
    {
        $path = $this->app->basePath('resources/validation');

        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }

        file_put_contents($path . '/' . $filename, json_encode($rules));

        return $filename;
    }

    /**
     * Clean up test schema files.
     */
    protected function tearDown(): void
    {
        $path = $this->app->basePath('resources/validation');
        if (is_dir($path)) {
            $files = glob($path . '/test-*.json');
            foreach ($files as $file) {
                unlink($file);
            }
        }

        parent::tearDown();
    }

    /**
     * Test route parameter validation with inline rules.
     */
    public function testRouteParamValidationWithInlineRules()
    {
        $router = $this->app['router'];

        $router->get('/users/{id}', function ($id) {
            return response()->json(['id' => $id]);
        })->validateRouteParams([
            'id' => 'required|integer|min:1'
        ]);

        // Test valid request
        $request = Request::create('/users/123', 'GET');
        $request->setRouteResolver(function () use ($router, $request) {
            return $router->getRoutes()->match($request);
        });

        $response = $this->app->handle($request);
        $this->assertEquals(200, $response->getStatusCode());

        // Test invalid request (non-integer)
        $request = Request::create('/users/abc', 'GET');
        $request->setRouteResolver(function () use ($router, $request) {
            return $router->getRoutes()->match($request);
        });

        try {
            $response = $this->app->handle($request);
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
        $router = $this->app['router'];

        $router->get('/search', function () {
            return response()->json(['results' => []]);
        })->validateQuery([
            'q' => 'required|string|min:3',
            'page' => 'integer|min:1'
        ]);

        // Test valid request
        $request = Request::create('/search?q=test&page=1', 'GET');
        $request->setRouteResolver(function () use ($router, $request) {
            return $router->getRoutes()->match($request);
        });

        $response = $this->app->handle($request);
        $this->assertEquals(200, $response->getStatusCode());

        // Test invalid request (missing required query param)
        $request = Request::create('/search', 'GET');
        $request->setRouteResolver(function () use ($router, $request) {
            return $router->getRoutes()->match($request);
        });

        try {
            $response = $this->app->handle($request);
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
            'email' => 'required|email'
        ];

        $schemaFile = $this->createTestSchemaFile($rules, 'user-validation.json');

        $router = $this->app['router'];

        $router->post('/users', function () {
            return response()->json(['message' => 'User created']);
        })->validateRequestBy($schemaFile, true); // validateAll = true

        // Test valid request
        $request = Request::create('/users', 'POST', [
            'name' => 'John Doe',
            'email' => 'john@example.com'
        ]);
        $request->setRouteResolver(function () use ($router, $request) {
            return $router->getRoutes()->match($request);
        });

        $response = $this->app->handle($request);
        $this->assertEquals(200, $response->getStatusCode());

        // Test invalid request (invalid email)
        $request = Request::create('/users', 'POST', [
            'name' => 'John Doe',
            'email' => 'invalid-email'
        ]);
        $request->setRouteResolver(function () use ($router, $request) {
            return $router->getRoutes()->match($request);
        });

        try {
            $response = $this->app->handle($request);
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
            'profile.settings.*.value' => 'required'
        ];

        $schemaFile = $this->createTestSchemaFile($rules, 'profile-validation.json');

        $router = $this->app['router'];

        $router->post('/users/{id}/profile', function ($id) {
            return response()->json(['message' => 'Profile updated']);
        })->validateRequestBy($schemaFile, true);

        // Test valid request with nested data
        $request = Request::create('/users/123/profile', 'POST', [
            'id' => 123,
            'profile' => [
                'name' => 'John Doe',
                'settings' => [
                    ['key' => 'theme', 'value' => 'dark'],
                    ['key' => 'language', 'value' => 'en']
                ]
            ]
        ]);
        $request->setRouteResolver(function () use ($router, $request) {
            return $router->getRoutes()->match($request);
        });

        $response = $this->app->handle($request);
        $this->assertEquals(200, $response->getStatusCode());

        // Test invalid request (missing nested required field)
        $request = Request::create('/users/123/profile', 'POST', [
            'id' => 123,
            'profile' => [
                'settings' => [
                    ['key' => 'theme'], // missing value
                ]
            ]
        ]);
        $request->setRouteResolver(function () use ($router, $request) {
            return $router->getRoutes()->match($request);
        });

        try {
            $response = $this->app->handle($request);
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

        $router = $this->app['router'];

        $router->post('/users', function () {
            return response()->json(['message' => 'User created']);
        })->validateRequestBy([
            'name' => 'required|string|max:255',
            'email' => 'required|email'
        ]);
    }

    /**
     * Test validateQuery throws exception when passed a file path.
     */
    public function testValidateQueryThrowsExceptionWithFilePath()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('validateQuery() only accepts arrays of validation rules. Use validateRequestBy() for JSON file paths.');

        $router = $this->app['router'];

        $router->get('/search', function () {
            return response()->json(['results' => []]);
        })->validateQuery('search-validation');
    }
}
