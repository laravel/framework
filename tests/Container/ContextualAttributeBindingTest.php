<?php

namespace Illuminate\Tests\Container;

use Attribute;
use Illuminate\Auth\AuthManager;
use Illuminate\Cache\CacheManager;
use Illuminate\Cache\Repository as CacheRepository;
use Illuminate\Config\Repository;
use Illuminate\Container\Attributes\Auth;
use Illuminate\Container\Attributes\Authenticated;
use Illuminate\Container\Attributes\Cache;
use Illuminate\Container\Attributes\Config;
use Illuminate\Container\Attributes\Context;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Container\Attributes\Database;
use Illuminate\Container\Attributes\Give;
use Illuminate\Container\Attributes\Log;
use Illuminate\Container\Attributes\RouteParameter;
use Illuminate\Container\Attributes\Storage;
use Illuminate\Container\Attributes\Tag;
use Illuminate\Container\Container;
use Illuminate\Container\RewindableGenerator;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Guard as GuardContract;
use Illuminate\Contracts\Container\ContextualAttribute;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Database\Connection;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Http\Request;
use Illuminate\Log\Context\Repository as ContextRepository;
use Illuminate\Log\LogManager;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class ContextualAttributeBindingTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testDependencyCanBeResolvedFromAttributeBinding()
    {
        $container = new Container;

        $container->bind(ContainerTestContract::class, fn (): ContainerTestImplB => new ContainerTestImplB);
        $container->whenHasAttribute(ContainerTestAttributeThatResolvesContractImpl::class, function (ContainerTestAttributeThatResolvesContractImpl $attribute) {
            return match ($attribute->name) {
                'A' => new ContainerTestImplA,
                'B' => new ContainerTestImplB
            };
        });

        $classA = $container->make(ContainerTestHasAttributeThatResolvesToImplA::class);

        $this->assertInstanceOf(ContainerTestHasAttributeThatResolvesToImplA::class, $classA);
        $this->assertInstanceOf(ContainerTestImplA::class, $classA->property);

        $classB = $container->make(ContainerTestHasAttributeThatResolvesToImplB::class);

        $this->assertInstanceOf(ContainerTestHasAttributeThatResolvesToImplB::class, $classB);
        $this->assertInstanceOf(ContainerTestImplB::class, $classB->property);
    }

    public function testSimpleDependencyCanBeResolvedCorrectlyFromGiveAttributeBinding()
    {
        $container = new Container;

        $container->bind(ContainerTestContract::class, concrete: ContainerTestImplA::class);

        $resolution = $container->make(GiveTestSimple::class);

        $this->assertInstanceOf(SimpleDependency::class, $resolution->dependency);
    }

    public function testComplexDependencyCanBeResolvedCorrectlyFromGiveAttributeBinding()
    {
        $container = new Container;

        $container->bind(ContainerTestContract::class, concrete: ContainerTestImplA::class);

        $resolution = $container->make(GiveTestComplex::class);

        $this->assertInstanceOf(ComplexDependency::class, $resolution->dependency);
        $this->assertTrue($resolution->dependency->param);
    }

    public function testScalarDependencyCanBeResolvedFromAttributeBinding()
    {
        $container = new Container;
        $container->singleton('config', fn () => new Repository([
            'app' => [
                'timezone' => 'Europe/Paris',
            ],
        ]));

        $container->whenHasAttribute(ContainerTestConfigValue::class, function (ContainerTestConfigValue $attribute, Container $container) {
            return $container->make('config')->get($attribute->key);
        });

        $class = $container->make(ContainerTestHasConfigValueProperty::class);

        $this->assertInstanceOf(ContainerTestHasConfigValueProperty::class, $class);
        $this->assertEquals('Europe/Paris', $class->timezone);
    }

    public function testScalarDependencyCanBeResolvedFromAttributeResolveMethod()
    {
        $container = new Container;
        $container->singleton('config', fn () => new Repository([
            'app' => [
                'env' => 'production',
            ],
        ]));

        $class = $container->make(ContainerTestHasConfigValueWithResolveProperty::class);

        $this->assertInstanceOf(ContainerTestHasConfigValueWithResolveProperty::class, $class);
        $this->assertEquals('production', $class->env);
    }

    public function testDependencyWithAfterCallbackAttributeCanBeResolved()
    {
        $container = new Container;

        $class = $container->make(ContainerTestHasConfigValueWithResolvePropertyAndAfterCallback::class);

        $this->assertEquals('Developer', $class->person->role);
    }

    public function testAuthedAttribute()
    {
        $container = new Container;
        $container->singleton('auth', function () {
            $manager = m::mock(AuthManager::class);
            $manager->shouldReceive('userResolver')->andReturn(fn ($guard = null) => $manager->guard($guard)->user());
            $manager->shouldReceive('guard')->with('foo')->andReturnUsing(function () {
                $guard = m::mock(GuardContract::class);
                $guard->shouldReceive('user')->andReturn(m:mock(AuthenticatableContract::class));

                return $guard;
            });
            $manager->shouldReceive('guard')->with('bar')->andReturnUsing(function () {
                $guard = m::mock(GuardContract::class);
                $guard->shouldReceive('user')->andReturn(m:mock(AuthenticatableContract::class));

                return $guard;
            });

            return $manager;
        });

        $container->make(AuthedTest::class);
    }

    public function testCacheAttribute()
    {
        $container = new Container;
        $container->singleton('cache', function () {
            $manager = m::mock(CacheManager::class);
            $manager->shouldReceive('store')->with('foo')->andReturn(m::mock(CacheRepository::class));
            $manager->shouldReceive('store')->with('bar')->andReturn(m::mock(CacheRepository::class));

            return $manager;
        });

        $container->make(CacheTest::class);
    }

    public function testConfigAttribute()
    {
        $container = new Container;
        $container->singleton('config', function () {
            $repository = m::mock(Repository::class);
            $repository->shouldReceive('get')->with('foo', null)->andReturn('foo');
            $repository->shouldReceive('get')->with('bar', null)->andReturn('bar');

            return $repository;
        });

        $container->make(ConfigTest::class);
    }

    public function testDatabaseAttribute()
    {
        $container = new Container;
        $container->singleton('db', function () {
            $manager = m::mock(DatabaseManager::class);
            $manager->shouldReceive('connection')->with('foo')->andReturn(m::mock(Connection::class));
            $manager->shouldReceive('connection')->with('bar')->andReturn(m::mock(Connection::class));

            return $manager;
        });

        $container->make(DatabaseTest::class);
    }

    public function testAuthAttribute()
    {
        $container = new Container; //
        $container->singleton('auth', function () {
            $manager = m::mock(AuthManager::class);
            $manager->shouldReceive('guard')->with('foo')->andReturn(m::mock(GuardContract::class));
            $manager->shouldReceive('guard')->with('bar')->andReturn(m::mock(GuardContract::class));

            return $manager;
        });

        $container->make(GuardTest::class);
    }

    public function testLogAttribute()
    {
        $container = new Container;
        $container->singleton('log', function () {
            $manager = m::mock(LogManager::class);
            $manager->shouldReceive('channel')->with('foo')->andReturn(m::mock(LoggerInterface::class));
            $manager->shouldReceive('channel')->with('bar')->andReturn(m::mock(LoggerInterface::class));

            return $manager;
        });

        $container->make(LogTest::class);
    }

    public function testRouteParameterAttribute()
    {
        $container = new Container;
        $container->singleton('request', function () {
            $request = m::mock(Request::class);
            $request->shouldReceive('route')->with('foo')->andReturn(m::mock(Model::class));
            $request->shouldReceive('route')->with('bar')->andReturn('bar');

            return $request;
        });

        $container->make(RouteParameterTest::class);
    }

    public function testContextAttribute(): void
    {
        $container = new Container;

        $container->singleton(ContextRepository::class, function () {
            $context = m::mock(ContextRepository::class);
            $context->shouldReceive('get')->once()->with('foo', null)->andReturn('foo');

            return $context;
        });

        $container->make(ContextTest::class);
    }

    public function testContextAttributeInteractingWithHidden(): void
    {
        $container = new Container;

        $container->singleton(ContextRepository::class, function () {
            $context = m::mock(ContextRepository::class);
            $context->shouldReceive('getHidden')->once()->with('bar', null)->andReturn('bar');
            $context->shouldNotReceive('get');

            return $context;
        });

        $container->make(ContextHiddenTest::class);
    }

    public function testStorageAttribute()
    {
        $container = new Container;
        $container->singleton('filesystem', function () {
            $manager = m::mock(FilesystemManager::class);
            $manager->shouldReceive('disk')->with('foo')->andReturn(m::mock(Filesystem::class));
            $manager->shouldReceive('disk')->with('bar')->andReturn(m::mock(Filesystem::class));

            return $manager;
        });

        $container->make(StorageTest::class);
    }

    public function testInjectionWithAttributeOnAppCall()
    {
        $container = new Container;

        $person = $container->call(function (ContainerTestHasConfigValueWithResolvePropertyAndAfterCallback $hasAttribute) {
            return $hasAttribute->person;
        });

        $this->assertEquals('Taylor', $person->name);
    }

    public function testAttributeOnAppCall()
    {
        $container = new Container;
        $container->singleton('config', fn () => new Repository([
            'app' => [
                'timezone' => 'Europe/Paris',
                'locale' => null,
            ],
        ]));

        $value = $container->call(function (#[Config('app.timezone')] string $value) {
            return $value;
        });

        $this->assertEquals('Europe/Paris', $value);

        $value = $container->call(function (#[Config('app.locale')] ?string $value) {
            return $value;
        });

        $this->assertNull($value);
    }

    public function testNestedAttributeOnAppCall()
    {
        $container = new Container;
        $container->singleton('config', fn () => new Repository([
            'app' => [
                'timezone' => 'Europe/Paris',
                'locale' => null,
            ],
        ]));

        $value = $container->call(function (TimezoneObject $object) {
            return $object;
        });

        $this->assertEquals('Europe/Paris', $value->timezone);

        $value = $container->call(function (LocaleObject $object) {
            return $object;
        });

        $this->assertNull($value->locale);
    }

    public function testTagAttribute()
    {
        $container = new Container;
        $container->bind('one', fn (): int => 1);
        $container->bind('two', fn (): int => 2);
        $container->tag(['one', 'two'], 'numbers');

        $value = $container->call(function (#[Tag('numbers')] RewindableGenerator $integers) {
            return $integers;
        });

        $this->assertEquals([1, 2], iterator_to_array($value));
    }
}

#[Attribute(Attribute::TARGET_PARAMETER)]
class ContainerTestAttributeThatResolvesContractImpl implements ContextualAttribute
{
    public function __construct(
        public readonly string $name
    ) {
    }
}

interface ContainerTestContract
{
}

final class ContainerTestImplA implements ContainerTestContract
{
}

final class ContainerTestImplB implements ContainerTestContract
{
}

final class ContainerTestHasAttributeThatResolvesToImplA
{
    public function __construct(
        #[ContainerTestAttributeThatResolvesContractImpl('A')]
        public readonly ContainerTestContract $property
    ) {
    }
}

final class ContainerTestHasAttributeThatResolvesToImplB
{
    public function __construct(
        #[ContainerTestAttributeThatResolvesContractImpl('B')]
        public readonly ContainerTestContract $property
    ) {
    }
}

#[Attribute(Attribute::TARGET_PARAMETER)]
final class ContainerTestConfigValue implements ContextualAttribute
{
    public function __construct(
        public readonly string $key
    ) {
    }
}

final class ContainerTestHasConfigValueProperty
{
    public function __construct(
        #[ContainerTestConfigValue('app.timezone')]
        public string $timezone
    ) {
    }
}

#[Attribute(Attribute::TARGET_PARAMETER)]
final class ContainerTestConfigValueWithResolve implements ContextualAttribute
{
    public function __construct(
        public readonly string $key
    ) {
    }

    public function resolve(self $attribute, Container $container): string
    {
        return $container->make('config')->get($attribute->key);
    }
}

final class ContainerTestHasConfigValueWithResolveProperty
{
    public function __construct(
        #[ContainerTestConfigValueWithResolve('app.env')]
        public string $env
    ) {
    }
}

#[Attribute(Attribute::TARGET_PARAMETER)]
final class ContainerTestConfigValueWithResolveAndAfter implements ContextualAttribute
{
    public function resolve(self $attribute, Container $container): object
    {
        return (object) ['name' => 'Taylor'];
    }

    public function after(self $attribute, object $value, Container $container): void
    {
        $value->role = 'Developer';
    }
}

final class ContainerTestHasConfigValueWithResolvePropertyAndAfterCallback
{
    public function __construct(
        #[ContainerTestConfigValueWithResolveAndAfter]
        public object $person
    ) {
    }
}

final class SimpleDependency implements ContainerTestContract
{
}

final class ComplexDependency implements ContainerTestContract
{
    public function __construct(public bool $param)
    {
    }
}

final class AuthedTest
{
    public function __construct(#[Authenticated('foo')] AuthenticatableContract $foo, #[CurrentUser('bar')] AuthenticatableContract $bar)
    {
    }
}

final class CacheTest
{
    public function __construct(#[Cache('foo')] CacheRepository $foo, #[Cache('bar')] CacheRepository $bar)
    {
    }
}

final class ConfigTest
{
    public function __construct(#[Config('foo')] string $foo, #[Config('bar')] string $bar)
    {
    }
}

final class ContextTest
{
    public function __construct(#[Context('foo')] string $foo)
    {
    }
}

final class ContextHiddenTest
{
    public function __construct(#[Context('bar', hidden: true)] string $foo)
    {
    }
}

final class DatabaseTest
{
    public function __construct(#[Database('foo')] Connection $foo, #[Database('bar')] Connection $bar)
    {
    }
}

final class GuardTest
{
    public function __construct(#[Auth('foo')] GuardContract $foo, #[Auth('bar')] GuardContract $bar)
    {
    }
}

final class LogTest
{
    public function __construct(#[Log('foo')] LoggerInterface $foo, #[Log('bar')] LoggerInterface $bar)
    {
    }
}

final class RouteParameterTest
{
    public function __construct(#[RouteParameter('foo')] Model $foo, #[RouteParameter('bar')] string $bar)
    {
    }
}

final class StorageTest
{
    public function __construct(#[Storage('foo')] Filesystem $foo, #[Storage('bar')] Filesystem $bar)
    {
    }
}

final class GiveTestSimple
{
    public function __construct(
        #[Give(SimpleDependency::class)]
        public readonly ContainerTestContract $dependency
    ) {
    }
}

final class GiveTestComplex
{
    public function __construct(
        #[Give(ComplexDependency::class, ['param' => true])]
        public readonly ContainerTestContract $dependency
    ) {
    }
}

final class TimezoneObject
{
    public function __construct(
        #[Config('app.timezone')] public readonly ?string $timezone
    ) {
        //
    }
}

final class LocaleObject
{
    public function __construct(
        #[Config('app.locale')] public readonly ?string $locale
    ) {
        //
    }
}
