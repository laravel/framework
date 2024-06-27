<?php

namespace Illuminate\Tests\Container;

use Attribute;
use Illuminate\Container\Container;
use PHPUnit\Framework\TestCase;

class AfterResolvingAttributeCallbackTest extends TestCase
{
    public function testCallbackIsCalledAfterDependencyResolutionWithAttribute()
    {
        $container = new Container();

        $container->afterResolvingAttribute(ContainerTestOnTenant::class, function (ContainerTestOnTenant $attribute, HasTenantImpl $hasTenantImpl, Container $container) {
            $hasTenantImpl->onTenant($attribute->tenant);
        });

        $hasTenantA = $container->make(ContainerTestHasTenantImplPropertyWithTenantA::class);
        $this->assertInstanceOf(HasTenantImpl::class, $hasTenantA->property);
        $this->assertEquals(Tenant::TenantA, $hasTenantA->property->tenant);

        $hasTenantB = $container->make(ContainerTestHasTenantImplPropertyWithTenantB::class);
        $this->assertInstanceOf(HasTenantImpl::class, $hasTenantB->property);
        $this->assertEquals(Tenant::TenantB, $hasTenantB->property->tenant);
    }

    public function testCallbackIsCalledAfterClassWithAttributeIsResolved()
    {
        $container = new Container();

        $container->afterResolvingAttribute(ContainerTestConfiguresClass::class, function (ContainerTestConfiguresClass $attribute, $class) {
            $class->value = $attribute->value;
        });

        $instance = $container->make(ContainerTestHasSelfConfiguringAttribute::class);

        $this->assertInstanceOf(ContainerTestHasSelfConfiguringAttribute::class, $instance);
        $this->assertEquals('foo', $instance->value);
    }

    public function testCallbackIsCalledAfterClassWithConstructorAndAttributeIsResolved()
    {
        $container = new Container();

        $container->afterResolvingAttribute(ContainerTestConfiguresClass::class, function (ContainerTestConfiguresClass $attribute, $class) {
            $class->value = $attribute->value;
        });

        $container->when(ContainerTestHasSelfConfiguringAttributeAndConstructor::class)
            ->needs('$value')
            ->give('no-the-right-value');

        $instance = $container->make(ContainerTestHasSelfConfiguringAttributeAndConstructor::class);

        $this->assertInstanceOf(ContainerTestHasSelfConfiguringAttributeAndConstructor::class, $instance);
        $this->assertEquals('the-right-value', $instance->value);
    }
}

#[Attribute(Attribute::TARGET_PARAMETER)]
final class ContainerTestOnTenant
{
    public function __construct(
        public readonly Tenant $tenant
    ) {
    }
}

enum Tenant
{
    case TenantA;
    case TenantB;
}

final class HasTenantImpl
{
    public ?Tenant $tenant = null;

    public function onTenant(Tenant $tenant): void
    {
        $this->tenant = $tenant;
    }
}

final class ContainerTestHasTenantImplPropertyWithTenantA
{
    public function __construct(
        #[ContainerTestOnTenant(Tenant::TenantA)]
        public readonly HasTenantImpl $property
    ) {
    }
}

final class ContainerTestHasTenantImplPropertyWithTenantB
{
    public function __construct(
        #[ContainerTestOnTenant(Tenant::TenantB)]
        public readonly HasTenantImpl $property
    ) {
    }
}

#[Attribute(Attribute::TARGET_CLASS)]
final class ContainerTestConfiguresClass
{
    public function __construct(
        public readonly string $value
    ) {
    }
}

#[ContainerTestConfiguresClass(value: 'foo')]
final class ContainerTestHasSelfConfiguringAttribute
{
    public ?string $value = null;
}

#[ContainerTestConfiguresClass(value: 'the-right-value')]
final class ContainerTestHasSelfConfiguringAttributeAndConstructor
{
    public function __construct(
        public string $value
    ) {
    }
}
