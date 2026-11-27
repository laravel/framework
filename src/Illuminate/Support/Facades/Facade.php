<?php

namespace Illuminate\Support\Facades;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Benchmark;
use Illuminate\Support\Collection;
use Illuminate\Support\Js;
use Illuminate\Support\Number;
use Illuminate\Support\Str;
use Illuminate\Support\Testing\Fakes\Fake;
use Illuminate\Support\Traits\Macroable;
use Illuminate\Support\Uri;
use JMac\Testing\Double;
use JMac\Testing\DoubleInterface;
use JMac\Testing\Exceptions\UnknownMethodException;
use Mockery;
use Mockery\LegacyMockInterface;
use ReflectionClass;
use ReflectionMethod;
use RuntimeException;

abstract class Facade
{
    /**
     * The application instance being facaded.
     *
     * @var \Illuminate\Contracts\Foundation\Application|null
     */
    protected static $app;

    /**
     * The resolved object instances.
     *
     * @var array
     */
    protected static $resolvedInstance;

    /**
     * The doubles swapped in behind the facades.
     *
     * @var array<string, \JMac\Testing\DoubleInterface>
     */
    protected static $doubles = [];

    /**
     * Indicates if Mockery should be used instead of Double to mock facades and container bindings.
     *
     * @var bool
     */
    protected static $usesMockery = true;

    /**
     * Indicates if the resolved instance should be cached.
     *
     * @var bool
     */
    protected static $cached = true;

    /**
     * Run a Closure when the facade has been resolved.
     *
     * @param  \Closure  $callback
     * @return void
     */
    public static function resolved(Closure $callback)
    {
        $accessor = static::getFacadeAccessor();

        if (static::$app->resolved($accessor) === true) {
            $callback(static::getFacadeRoot(), static::$app);
        }

        static::$app->afterResolving($accessor, function ($service, $app) use ($callback) {
            $callback($service, $app);
        });
    }

    /**
     * Use Mockery instead of Double to mock facades and container bindings.
     *
     * @return void
     */
    public static function useMockery()
    {
        static::$usesMockery = true;
    }

    /**
     * Use Double to mock facades and container bindings.
     *
     * Call this before the test case's parent::setUp(), or in the test bootstrap, so the
     * expectations of the doubles are verified when each test ends.
     *
     * @return void
     */
    public static function useDouble()
    {
        static::$usesMockery = false;
    }

    /**
     * Determine if Mockery is used instead of Double to mock facades and container bindings.
     *
     * @return bool
     */
    public static function usesMockery()
    {
        return static::$usesMockery;
    }

    /**
     * Convert the facade into a spy.
     *
     * @return \Mockery\MockInterface
     */
    public static function spy()
    {
        if (! static::isMock()) {
            if (static::$usesMockery) {
                static::ensureMockingLibraryIsInstalled();

                $class = static::getMockableClass();

                return tap($class ? Mockery::spy($class) : Mockery::spy(), function ($spy) {
                    static::swap($spy);
                });
            }

            return static::createFreshMockInstance();
        }
    }

    /**
     * Initiate a partial mock on the facade.
     *
     * @return \Mockery\MockInterface
     */
    public static function partialMock()
    {
        $mock = static::isMock()
            ? static::getMock()
            : static::createFreshMockInstance();

        return $mock instanceof LegacyMockInterface
            ? $mock->makePartial()
            : $mock->passthru();
    }

    /**
     * Initiate a mock expectation on the facade.
     *
     * @return \Mockery\Expectation
     */
    public static function shouldReceive()
    {
        $mock = static::getOrCreateMock();

        if ($mock instanceof LegacyMockInterface) {
            return $mock->shouldReceive(...func_get_args());
        }

        $args = func_get_args();

        return static::explainForwardedMethods(fn () => $mock->allows(...$args));
    }

    /**
     * Initiate a mock expectation on the facade.
     *
     * @return \Mockery\Expectation
     */
    public static function expects()
    {
        $args = func_get_args();

        return static::explainForwardedMethods(fn () => static::getOrCreateMock()->expects(...$args));
    }

    /**
     * Initiate a mock expectation that the given method is never called on the facade.
     *
     * @param  string  $method
     * @return \Mockery\Expectation
     */
    public static function shouldNotReceive($method)
    {
        $mock = static::$usesMockery
            ? static::getMockOrRoot()
            : static::getOrCreateMock();

        return $mock instanceof DoubleInterface
            ? static::explainForwardedMethods(fn () => $mock->expects($method)->never())
            : $mock->shouldNotReceive(...func_get_args());
    }

    /**
     * Assert that the mocked facade received the given method.
     *
     * @param  string|null  $method
     * @param  array|null  $args
     * @return \Mockery\HigherOrderMessage|\Mockery\VerificationDirector
     *
     * @throws \RuntimeException
     */
    public static function shouldHaveReceived($method = null, $args = null)
    {
        $mock = static::$usesMockery
            ? static::getMockOrRoot()
            : static::getMockOrFail();

        if (! $mock instanceof DoubleInterface) {
            return $mock->shouldHaveReceived(...func_get_args());
        }

        $assertion = static::explainForwardedMethods(fn () => $mock->received($method));

        return is_null($args) ? $assertion : $assertion->with(...$args);
    }

    /**
     * Assert that the mocked facade did not receive the given method.
     *
     * @param  string|null  $method
     * @param  array|null  $args
     * @return \Mockery\HigherOrderMessage|null
     *
     * @throws \RuntimeException
     */
    public static function shouldNotHaveReceived($method = null, $args = null)
    {
        $mock = static::$usesMockery
            ? static::getMockOrRoot()
            : static::getMockOrFail();

        if (! $mock instanceof DoubleInterface) {
            return $mock->shouldNotHaveReceived(...func_get_args());
        }

        $assertion = static::explainForwardedMethods(fn () => $mock->received($method));

        return (is_null($args) ? $assertion : $assertion->with(...$args))->never();
    }

    /**
     * Get the alternative to mocking the methods the facade forwards.
     *
     * @return string|null
     */
    protected static function testingAlternative()
    {
        return null;
    }

    /**
     * Run the given callback, explaining any method the facade root only forwards.
     *
     * @template TReturn
     *
     * @param  \Closure(): TReturn  $callback
     * @return TReturn
     *
     * @throws \RuntimeException
     */
    protected static function explainForwardedMethods(Closure $callback)
    {
        try {
            return $callback();
        } catch (UnknownMethodException $e) {
            if (! is_null($e->suggestion) || ! method_exists($e->target, '__call')) {
                throw $e;
            }

            $facade = class_basename(static::class);

            $handlesMacrosOnly = (new ReflectionMethod($e->target, '__call'))->getFileName()
                === (new ReflectionClass(Macroable::class))->getFileName();

            if ($handlesMacrosOnly && ! $e->target::hasMacro($e->method)) {
                throw $e;
            }

            $message = $handlesMacrosOnly
                ? sprintf("`%s::%s()` is a macro, so it can't be mocked with Double.", $facade, $e->method)
                : sprintf("`%s::%s()` is forwarded through `%s::__call()`, so it can't be mocked with Double.", $facade, $e->method, class_basename($e->target));

            throw new RuntimeException(implode(' ', array_filter([
                $message,
                static::testingAlternative(),
                'Or call `Facade::useMockery()` to mock it with Mockery.',
            ])), previous: $e);
        }
    }

    /**
     * Get the existing mock for the facade or create a strict one.
     *
     * @return \JMac\Testing\DoubleInterface|\Mockery\MockInterface
     */
    protected static function getOrCreateMock()
    {
        if (static::isMock()) {
            return static::getMock();
        }

        $mock = static::createFreshMockInstance();

        return $mock instanceof DoubleInterface ? $mock->strict() : $mock;
    }

    /**
     * Get the existing mock for the facade or throw an exception.
     *
     * @return \JMac\Testing\DoubleInterface|\Mockery\MockInterface
     *
     * @throws \RuntimeException
     */
    protected static function getMockOrFail()
    {
        return static::getMock() ?? throw new RuntimeException(
            sprintf('The [%s] facade must be spied or mocked before asserting what it received.', static::class)
        );
    }

    /**
     * Get the double behind the facade, or the root the facade forwards calls to.
     *
     * @return mixed
     *
     * @throws \RuntimeException
     */
    protected static function getMockOrRoot()
    {
        $root = static::getFacadeRoot();

        if (! $root) {
            throw new RuntimeException('A facade root has not been set.');
        }

        $mock = static::resolveMock($root);

        return $mock instanceof DoubleInterface ? $mock : $root;
    }

    /**
     * Create a fresh mock instance for the given class.
     *
     * @return \JMac\Testing\DoubleInterface|\Mockery\MockInterface
     */
    protected static function createFreshMockInstance()
    {
        return tap(static::createMock(), function ($mock) {
            static::swap($mock);

            if ($mock instanceof LegacyMockInterface) {
                $mock->shouldAllowMockingProtectedMethods();
            }
        });
    }

    /**
     * Create a fresh mock instance for the given class.
     *
     * @return \JMac\Testing\DoubleInterface|\Mockery\MockInterface
     *
     * @throws \RuntimeException
     */
    protected static function createMock()
    {
        static::ensureMockingLibraryIsInstalled();

        if (static::$usesMockery) {
            $class = static::getMockableClass();

            return $class ? Mockery::mock($class) : Mockery::mock();
        }

        if (! is_object($root = static::getFacadeRoot())) {
            throw new RuntimeException(
                sprintf('Unable to mock the [%s] facade because it does not have a resolvable root.', static::class)
            );
        }

        return Double::for($root, override: true);
    }

    /**
     * Ensure the library used to mock facades is installed.
     *
     * @return void
     *
     * @throws \RuntimeException
     */
    protected static function ensureMockingLibraryIsInstalled()
    {
        if (static::$usesMockery && ! class_exists(Mockery::class)) {
            throw new RuntimeException('Mocking with Mockery requires the [mockery/mockery] package. Install it using `composer require --dev mockery/mockery` or call `Facade::useDouble()`.');
        }

        if (! static::$usesMockery && ! class_exists(Double::class)) {
            throw new RuntimeException('Mocking requires the [jasonmccreary/double] package. Install it using `composer require --dev jasonmccreary/double` or call `Facade::useMockery()`.');
        }
    }

    /**
     * Determines whether a mock is set as the instance of the facade.
     *
     * @return bool
     */
    protected static function isMock()
    {
        return static::getMock() !== null;
    }

    /**
     * Get the mock set as the instance of the facade, if any.
     *
     * @return \JMac\Testing\DoubleInterface|\Mockery\MockInterface|null
     */
    protected static function getMock()
    {
        $root = static::$usesMockery
            ? (static::$resolvedInstance[static::getFacadeAccessor()] ?? null)
            : static::getFacadeRoot();

        return static::resolveMock($root);
    }

    /**
     * Get the mock for the given facade root, if it is one.
     *
     * @param  mixed  $root
     * @return \JMac\Testing\DoubleInterface|\Mockery\MockInterface|null
     */
    protected static function resolveMock($root)
    {
        $name = static::getFacadeAccessor();

        if (isset(static::$doubles[$name]) && static::$doubles[$name]->instance() === $root) {
            return static::$doubles[$name];
        }

        return $root instanceof DoubleInterface || $root instanceof LegacyMockInterface ? $root : null;
    }

    /**
     * Get the mockable class for the bound instance.
     *
     * @return string|null
     */
    protected static function getMockableClass()
    {
        if ($root = static::getFacadeRoot()) {
            return get_class($root);
        }
    }

    /**
     * Hotswap the underlying instance behind the facade.
     *
     * @param  mixed  $instance
     * @return void
     */
    public static function swap($instance)
    {
        $name = static::getFacadeAccessor();

        if ($instance instanceof DoubleInterface) {
            static::$doubles[$name] = $instance;

            $instance = $instance->instance();
        } else {
            unset(static::$doubles[$name]);
        }

        static::$resolvedInstance[$name] = $instance;

        if (isset(static::$app)) {
            static::$app->instance($name, $instance);
        }
    }

    /**
     * Determines whether a "fake" has been set as the facade instance.
     *
     * @return bool
     */
    public static function isFake()
    {
        $name = static::getFacadeAccessor();

        return isset(static::$resolvedInstance[$name]) &&
               static::$resolvedInstance[$name] instanceof Fake;
    }

    /**
     * Get the root object behind the facade.
     *
     * @return mixed
     */
    public static function getFacadeRoot()
    {
        return static::resolveFacadeInstance(static::getFacadeAccessor());
    }

    /**
     * Get the registered name of the component.
     *
     * @return string
     *
     * @throws \RuntimeException
     */
    protected static function getFacadeAccessor()
    {
        throw new RuntimeException('Facade does not implement getFacadeAccessor method.');
    }

    /**
     * Resolve the facade root instance from the container.
     *
     * @param  string  $name
     * @return mixed
     */
    protected static function resolveFacadeInstance($name)
    {
        if (isset(static::$resolvedInstance[$name])) {
            return static::$resolvedInstance[$name];
        }

        if (static::$app) {
            if (static::$cached) {
                return static::$resolvedInstance[$name] = static::$app[$name];
            }

            return static::$app[$name];
        }
    }

    /**
     * Clear a resolved facade instance.
     *
     * @param  ?string  $name
     * @return void
     */
    public static function clearResolvedInstance($name = null)
    {
        $name ??= static::getFacadeAccessor();

        unset(static::$resolvedInstance[$name], static::$doubles[$name]);
    }

    /**
     * Clear all of the resolved instances.
     *
     * @return void
     */
    public static function clearResolvedInstances()
    {
        static::$resolvedInstance = [];
        static::$doubles = [];
    }

    /**
     * Get the application default aliases.
     *
     * @return \Illuminate\Support\Collection
     */
    public static function defaultAliases()
    {
        return new Collection([
            'App' => App::class,
            'Arr' => Arr::class,
            'Artisan' => Artisan::class,
            'Auth' => Auth::class,
            'Benchmark' => Benchmark::class,
            'Blade' => Blade::class,
            'Broadcast' => Broadcast::class,
            'Bus' => Bus::class,
            'Cache' => Cache::class,
            'Concurrency' => Concurrency::class,
            'Config' => Config::class,
            'Context' => Context::class,
            'Cookie' => Cookie::class,
            'Crypt' => Crypt::class,
            'Date' => Date::class,
            'DB' => DB::class,
            'Eloquent' => Model::class,
            'Event' => Event::class,
            'File' => File::class,
            'Gate' => Gate::class,
            'Hash' => Hash::class,
            'Http' => Http::class,
            'Image' => Image::class,
            'Js' => Js::class,
            'Lang' => Lang::class,
            'Log' => Log::class,
            'Mail' => Mail::class,
            'Notification' => Notification::class,
            'Number' => Number::class,
            'Password' => Password::class,
            'Process' => Process::class,
            'Queue' => Queue::class,
            'RateLimiter' => RateLimiter::class,
            'Redirect' => Redirect::class,
            'Request' => Request::class,
            'Response' => Response::class,
            'Route' => Route::class,
            'Schedule' => Schedule::class,
            'Schema' => Schema::class,
            'Session' => Session::class,
            'Storage' => Storage::class,
            'Str' => Str::class,
            'Uri' => Uri::class,
            'URL' => URL::class,
            'Validator' => Validator::class,
            'View' => View::class,
            'Vite' => Vite::class,
        ]);
    }

    /**
     * Get the application instance behind the facade.
     *
     * @return \Illuminate\Contracts\Foundation\Application|null
     */
    public static function getFacadeApplication()
    {
        return static::$app;
    }

    /**
     * Set the application instance.
     *
     * @param  \Illuminate\Contracts\Foundation\Application|null  $app
     * @return void
     */
    public static function setFacadeApplication($app)
    {
        static::$app = $app;
    }

    /**
     * Handle dynamic, static calls to the object.
     *
     * @param  string  $method
     * @param  array  $args
     * @return mixed
     *
     * @throws \RuntimeException
     */
    public static function __callStatic($method, $args)
    {
        $instance = static::getFacadeRoot();

        if (! $instance) {
            throw new RuntimeException('A facade root has not been set.');
        }

        return $instance->$method(...$args);
    }
}
