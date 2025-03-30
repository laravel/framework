<?php

namespace Illuminate\Tests\Foundation\Http\Middleware;

use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfTokenAdvanced;
use Illuminate\Http\Request;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * A simple Application implementation for testing
 */
class TestApplication extends Container implements Application
{
    /**
     * The config repository instance.
     */
    protected $config;

    /**
     * Create a new TestApplication instance.
     */
    public function __construct(Repository $config)
    {
        parent::__construct();
        $this->config = $config;
        $this->instance('config', $config);
    }

    /**
     * Get the version number of the application.
     */
    public function version()
    {
        return '9.x-testing';
    }

    /**
     * Get the base path of the Laravel installation.
     */
    public function basePath($path = '')
    {
        return '/tmp' . ($path ? '/' . $path : '');
    }

    /**
     * Get the path to the bootstrap directory.
     */
    public function bootstrapPath($path = '')
    {
        return $this->basePath('bootstrap') . ($path ? '/' . $path : '');
    }

    /**
     * Get the path to the application configuration files.
     */
    public function configPath($path = '')
    {
        return $this->basePath('config') . ($path ? '/' . $path : '');
    }

    /**
     * Get the path to the database directory.
     */
    public function databasePath($path = '')
    {
        return $this->basePath('database') . ($path ? '/' . $path : '');
    }

    /**
     * Get the path to the language files.
     */
    public function langPath($path = '')
    {
        return $this->basePath('lang') . ($path ? '/' . $path : '');
    }

    /**
     * Get the path to the public directory.
     */
    public function publicPath($path = '')
    {
        return $this->basePath('public') . ($path ? '/' . $path : '');
    }

    /**
     * Get the path to the storage directory.
     */
    public function storagePath($path = '')
    {
        return $this->basePath('storage') . ($path ? '/' . $path : '');
    }

    /**
     * Get the path to the resources directory.
     */
    public function resourcePath($path = '')
    {
        return $this->basePath('resources') . ($path ? '/' . $path : '');
    }

    /**
     * Get or check the current application environment.
     */
    public function environment(...$environments)
    {
        return 'testing';
    }

    /**
     * Determine if the application is running in the console.
     */
    public function runningInConsole()
    {
        return false;
    }

    /**
     * Determine if the application is running unit tests.
     */
    public function runningUnitTests()
    {
        return true;
    }

    /**
     * Determine if the application is currently down for maintenance.
     */
    public function isDownForMaintenance()
    {
        return false;
    }

    /**
     * Register all of the configured providers.
     */
    public function registerConfiguredProviders()
    {
    }

    /**
     * Register a service provider with the application.
     */
    public function register($provider, $force = false)
    {
        return $provider;
    }

    /**
     * Register a deferred provider and service.
     */
    public function registerDeferredProvider($provider, $service = null)
    {
    }

    /**
     * Resolve a service provider instance from the class name.
     */
    public function resolveProvider($provider)
    {
        return new $provider($this);
    }

    /**
     * Boot the application's service providers.
     */
    public function boot()
    {
    }

    /**
     * Register a new boot listener.
     */
    public function booting($callback)
    {
    }

    /**
     * Register a new "booted" listener.
     */
    public function booted($callback)
    {
    }

    /**
     * Run the given array of bootstrap classes.
     */
    public function bootstrapWith(array $bootstrappers)
    {
    }

    /**
     * Get the service providers that have been loaded.
     */
    public function getProviders($provider)
    {
        return [];
    }

    /**
     * Determine if the application has been bootstrapped before.
     */
    public function hasBeenBootstrapped()
    {
        return true;
    }

    /**
     * Load and boot all of the remaining deferred providers.
     */
    public function loadDeferredProviders()
    {
    }

    /**
     * Register a terminating callback with the application.
     */
    public function terminating($callback)
    {
    }

    /**
     * Terminate the application.
     */
    public function terminate()
    {
    }

    /**
     * Determine if the given configuration is in debug/local mode.
     */
    public function hasDebugModeEnabled()
    {
        return false;
    }
}

class VerifyCsrfTokenAdvancedTest extends TestCase
{
    protected $app;
    protected $encrypter;
    protected $middleware;
    protected $request;
    protected $session;

    protected function setUp(): void
    {
        parent::setUp();

        $config = new Repository([
            'session' => [
                'lifetime' => 120,
                'path' => '/',
                'domain' => null,
            ],
            'security' => [
                'csrf' => [
                    'double_submit_cookie' => true,
                    'expiration' => 60,
                ],
            ],
        ]);

        // Create a real application instance for testing
        $this->app = new TestApplication($config);

        $this->encrypter = $this->createMock(Encrypter::class);
        
        $this->middleware = new VerifyCsrfTokenAdvanced($this->app, $this->encrypter);

        $this->request = new Request();

        $this->session = new Store('test-session', new ArraySessionHandler(1));
        $this->session->put('_token', 'test-token');

        $this->request->setLaravelSession($this->session);
    }

    public function testTokensMatch()
    {
        $this->request->headers->set('X-CSRF-TOKEN', 'test-token');

        $response = $this->middleware->handle($this->request, function () {
            return new Response('OK');
        });

        $this->assertEquals('OK', $response->getContent());
    }

    public function testTokensWithExpirationMatch()
    {
        // Token with future expiration
        $tokenWithExpiration = 'test-token|'.(time() + 3600);
        $this->request->headers->set('X-CSRF-TOKEN', $tokenWithExpiration);

        $response = $this->middleware->handle($this->request, function () {
            return new Response('OK');
        });

        $this->assertEquals('OK', $response->getContent());
    }

    public function testTokensWithExpiredTimeDoNotMatch()
    {
        $this->expectException(\Illuminate\Session\TokenMismatchException::class);

        // Token with past expiration
        $tokenWithExpiration = 'test-token|'.(time() - 3600);
        $this->request->headers->set('X-CSRF-TOKEN', $tokenWithExpiration);

        $this->middleware->handle($this->request, function () {
            return new Response('OK');
        });
    }

    public function testInvalidFormatTokensDoNotMatch()
    {
        $this->expectException(\Illuminate\Session\TokenMismatchException::class);

        // Token with invalid format
        $tokenWithExpiration = 'test-token|invalid-timestamp';
        $this->request->headers->set('X-CSRF-TOKEN', $tokenWithExpiration);

        $this->middleware->handle($this->request, function () {
            return new Response('OK');
        });
    }

    public function testCanSkipMiddlewareForExceptUrls()
    {
        $this->middleware->except = ['test/*'];

        $this->request->server->set('REQUEST_URI', 'test/route');

        $response = $this->middleware->handle($this->request, function () {
            return new Response('SKIPPED');
        });

        $this->assertEquals('SKIPPED', $response->getContent());
    }

    public function testAddsCookiesToResponse()
    {
        $this->request->headers->set('X-CSRF-TOKEN', 'test-token');

        $response = $this->middleware->handle($this->request, function () {
            return new Response('OK');
        });

        $cookies = $response->headers->getCookies();

        // Should add two cookies - XSRF-TOKEN and csrf_token
        $this->assertCount(2, $cookies);

        $cookieNames = array_map(function ($cookie) {
            return $cookie->getName();
        }, $cookies);

        $this->assertContains('XSRF-TOKEN', $cookieNames);
        $this->assertContains('csrf_token', $cookieNames);
    }
}
