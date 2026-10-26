<?php

namespace Illuminate\Tests\Container;

use Illuminate\Container\Attributes\Lazy;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Container\Container;
use Illuminate\Contracts\Container\BindingResolutionException;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class ContainerLazyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        LazyExpensiveService::$constructed = 0;
        LazyDependency::$constructed = 0;
        LazySingletonService::$constructed = 0;
        EagerService::$constructed = 0;
    }

    public function testLazyClassIsNotConstructedUntilItIsUsed()
    {
        $container = new Container;

        $service = $container->make(LazyExpensiveService::class);

        $this->assertSame(0, LazyExpensiveService::$constructed);
        $this->assertTrue((new ReflectionClass(LazyExpensiveService::class))->isUninitializedLazyObject($service));

        $this->assertSame('ready', $service->status());
        $this->assertSame(1, LazyExpensiveService::$constructed);
    }

    public function testLazyInstanceIsOfTheResolvedTypeBeforeItIsUsed()
    {
        $service = (new Container)->make(LazyExpensiveService::class);

        $this->assertInstanceOf(LazyExpensiveService::class, $service);
        $this->assertSame(0, LazyExpensiveService::$constructed);
    }

    public function testDependenciesAreResolvedOnFirstUse()
    {
        $service = (new Container)->make(LazyServiceWithDependency::class);

        $this->assertSame(0, LazyDependency::$constructed);

        $this->assertInstanceOf(LazyDependency::class, $service->dependency());
        $this->assertSame(1, LazyDependency::$constructed);
    }

    public function testContextualBindingsApplyOnFirstUse()
    {
        $container = new Container;

        $container->bind(LazyContract::class, LazyImplementationOne::class);
        $container->when(LazyServiceWithContract::class)
            ->needs(LazyContract::class)
            ->give(LazyImplementationTwo::class);

        $service = $container->make(LazyServiceWithContract::class);

        $this->assertInstanceOf(LazyImplementationTwo::class, $service->contract());
    }

    public function testParameterOverridesApplyOnFirstUse()
    {
        $container = new Container;

        $service = $container->make(LazyServiceWithPrimitive::class, ['name' => 'Taylor']);

        // Resolve something else in between so the override stack has moved on...
        $container->make(EagerService::class);

        $this->assertSame('Taylor', $service->name());
    }

    public function testLazySingletonIsSharedAndConstructedOnce()
    {
        $container = new Container;

        $first = $container->make(LazySingletonService::class);
        $second = $container->make(LazySingletonService::class);

        $this->assertSame($first, $second);
        $this->assertSame(0, LazySingletonService::$constructed);

        $first->status();
        $second->status();

        $this->assertSame(1, LazySingletonService::$constructed);
    }

    public function testClosureBindingsAreNotMadeLazy()
    {
        $container = new Container;

        $container->bind(LazyExpensiveService::class, fn () => new LazyExpensiveService);

        $service = $container->make(LazyExpensiveService::class);

        $this->assertFalse((new ReflectionClass(LazyExpensiveService::class))->isUninitializedLazyObject($service));
        $this->assertSame(1, LazyExpensiveService::$constructed);
    }

    public function testClassesWithoutTheAttributeAreBuiltImmediately()
    {
        (new Container)->make(EagerService::class);

        $this->assertSame(1, EagerService::$constructed);
    }

    public function testResolutionErrorsAreThrownOnFirstUse()
    {
        $service = (new Container)->make(LazyServiceWithUnresolvableDependency::class);

        $this->expectException(BindingResolutionException::class);

        $service->value();
    }
}

#[Lazy]
class LazyExpensiveService
{
    public static int $constructed = 0;

    protected string $status;

    public function __construct()
    {
        static::$constructed++;

        $this->status = 'ready';
    }

    public function status(): string
    {
        return $this->status;
    }
}

class LazyDependency
{
    public static int $constructed = 0;

    public function __construct()
    {
        static::$constructed++;
    }
}

#[Lazy]
class LazyServiceWithDependency
{
    public function __construct(protected LazyDependency $dependency)
    {
    }

    public function dependency(): LazyDependency
    {
        return $this->dependency;
    }
}

interface LazyContract
{
}

class LazyImplementationOne implements LazyContract
{
}

class LazyImplementationTwo implements LazyContract
{
}

#[Lazy]
class LazyServiceWithContract
{
    public function __construct(protected LazyContract $contract)
    {
    }

    public function contract(): LazyContract
    {
        return $this->contract;
    }
}

#[Lazy]
class LazyServiceWithPrimitive
{
    public function __construct(protected string $name)
    {
    }

    public function name(): string
    {
        return $this->name;
    }
}

#[Lazy]
#[Singleton]
class LazySingletonService
{
    public static int $constructed = 0;

    protected bool $booted;

    public function __construct()
    {
        static::$constructed++;

        $this->booted = true;
    }

    public function status(): bool
    {
        return $this->booted;
    }
}

class EagerService
{
    public static int $constructed = 0;

    public function __construct()
    {
        static::$constructed++;
    }
}

#[Lazy]
class LazyServiceWithUnresolvableDependency
{
    public function __construct(protected string $missing)
    {
    }

    public function value(): string
    {
        return $this->missing;
    }
}
