<?php

namespace Illuminate\Tests\Container;

use Attribute;
use Illuminate\Container\Container;
use Illuminate\Contracts\Container\ContextualAttribute;
use PHPUnit\Framework\TestCase;

class ContextualAttributeBindingTest extends TestCase
{
    public function testDependencyCanBeResolvedFromAttributeBinding()
    {
        $container = new Container;

        $container->bind(ContainerTestContract::class, fn () => new ContainerTestImplB);
        $container->whenHas(ContainerTestAttributeThatResolvesContractImpl::class, function (ContainerTestAttributeThatResolvesContractImpl $attribute) {
            return match ($attribute->name) {
                'A' => new ContainerTestImplA,
                'B' => new ContainerTestImplB
            };
        });

        $classA = $container->make(ContainerTestHasAttributeThatResolvesToImplA::class);

        $this->assertInstanceOf(ContainerTestHasAttributeThatResolvesToImplA::class, $classA);
        $this->assertInstanceOf(ContainerTestImplA::class, $classA->property);

        $classB = $container->make(ContainerTestHasAttributeThatResolvesToImplA::class);

        $this->assertInstanceOf(ContainerTestHasAttributeThatResolvesToImplA::class, $classB);
        $this->assertInstanceOf(ContainerTestImplA::class, $classB->property);
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
