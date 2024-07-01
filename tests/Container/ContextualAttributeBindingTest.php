<?php

namespace Illuminate\Tests\Container;

use Attribute;
use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Contracts\Container\ContextualAttribute;
use PHPUnit\Framework\TestCase;

class ContextualAttributeBindingTest extends TestCase
{
    public function testDependencyCanBeResolvedFromAttributeBinding()
    {
        $container = new Container;

        $container->bind(ContainerTestContract::class, fn () => new ContainerTestImplB);
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
