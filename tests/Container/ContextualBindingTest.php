<?php

namespace Illuminate\Tests\Container;

use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use PHPUnit\Framework\TestCase;

class ContextualBindingTest extends TestCase
{
    public function testContainerCanInjectDifferentImplementationsDependingOnContext()
    {
        $container = new Container;

        $container->bind(IContainerContextContractStub::class, ContainerContextImplementationStub::class);

        $container->when(ContainerTestContextInjectOne::class)->needs(IContainerContextContractStub::class)->give(ContainerContextImplementationStub::class);
        $container->when(ContainerTestContextInjectTwo::class)->needs(IContainerContextContractStub::class)->give(ContainerContextImplementationStubTwo::class);

        $one = $container->make(ContainerTestContextInjectOne::class);
        $two = $container->make(ContainerTestContextInjectTwo::class);

        $this->assertInstanceOf(ContainerContextImplementationStub::class, $one->impl);
        $this->assertInstanceOf(ContainerContextImplementationStubTwo::class, $two->impl);

        /*
         * Test With Closures
         */
        $container = new Container;

        $container->bind(IContainerContextContractStub::class, ContainerContextImplementationStub::class);

        $container->when(ContainerTestContextInjectOne::class)->needs(IContainerContextContractStub::class)->give(ContainerContextImplementationStub::class);
        $container->when(ContainerTestContextInjectTwo::class)->needs(IContainerContextContractStub::class)->give(function ($container) {
            return $container->make(ContainerContextImplementationStubTwo::class);
        });

        $one = $container->make(ContainerTestContextInjectOne::class);
        $two = $container->make(ContainerTestContextInjectTwo::class);

        $this->assertInstanceOf(ContainerContextImplementationStub::class, $one->impl);
        $this->assertInstanceOf(ContainerContextImplementationStubTwo::class, $two->impl);

        /*
         * Test nesting to make the same 'abstract' in different context
         */
        $container = new Container;

        $container->bind(IContainerContextContractStub::class, ContainerContextImplementationStub::class);

        $container->when(ContainerTestContextInjectOne::class)->needs(IContainerContextContractStub::class)->give(function ($container) {
            return $container->make(IContainerContextContractStub::class);
        });

        $one = $container->make(ContainerTestContextInjectOne::class);

        $this->assertInstanceOf(ContainerContextImplementationStub::class, $one->impl);
    }

    public function testContextualBindingWorksForExistingInstancedBindings()
    {
        $container = new Container;

        $container->instance(IContainerContextContractStub::class, new ContainerImplementationStub);

        $container->when(ContainerTestContextInjectOne::class)->needs(IContainerContextContractStub::class)->give(ContainerContextImplementationStubTwo::class);

        $this->assertInstanceOf(ContainerContextImplementationStubTwo::class, $container->make(ContainerTestContextInjectOne::class)->impl);
    }

    public function testContextualBindingWorksForNewlyInstancedBindings()
    {
        $container = new Container;

        $container->when(ContainerTestContextInjectOne::class)->needs(IContainerContextContractStub::class)->give(ContainerContextImplementationStubTwo::class);

        $container->instance(IContainerContextContractStub::class, new ContainerImplementationStub);

        $this->assertInstanceOf(
            ContainerContextImplementationStubTwo::class,
            $container->make(ContainerTestContextInjectOne::class)->impl
        );
    }

    public function testContextualBindingWorksOnExistingAliasedInstances()
    {
        $container = new Container;

        $container->instance('stub', new ContainerImplementationStub);
        $container->alias('stub', IContainerContextContractStub::class);

        $container->when(ContainerTestContextInjectOne::class)->needs(IContainerContextContractStub::class)->give(ContainerContextImplementationStubTwo::class);

        $this->assertInstanceOf(
            ContainerContextImplementationStubTwo::class,
            $container->make(ContainerTestContextInjectOne::class)->impl
        );
    }

    public function testContextualBindingWorksOnNewAliasedInstances()
    {
        $container = new Container;

        $container->when(ContainerTestContextInjectOne::class)->needs(IContainerContextContractStub::class)->give(ContainerContextImplementationStubTwo::class);

        $container->instance('stub', new ContainerImplementationStub);
        $container->alias('stub', IContainerContextContractStub::class);

        $this->assertInstanceOf(
            ContainerContextImplementationStubTwo::class,
            $container->make(ContainerTestContextInjectOne::class)->impl
        );
    }

    public function testContextualBindingWorksOnNewAliasedBindings()
    {
        $container = new Container;

        $container->when(ContainerTestContextInjectOne::class)->needs(IContainerContextContractStub::class)->give(ContainerContextImplementationStubTwo::class);

        $container->bind('stub', ContainerContextImplementationStub::class);
        $container->alias('stub', IContainerContextContractStub::class);

        $this->assertInstanceOf(
            ContainerContextImplementationStubTwo::class,
            $container->make(ContainerTestContextInjectOne::class)->impl
        );
    }

    public function testContextualBindingWorksForMultipleClasses()
    {
        $container = new Container;

        $container->bind(IContainerContextContractStub::class, ContainerContextImplementationStub::class);

        $container->when([ContainerTestContextInjectTwo::class, ContainerTestContextInjectThree::class])->needs(IContainerContextContractStub::class)->give(ContainerContextImplementationStubTwo::class);

        $this->assertInstanceOf(
            ContainerContextImplementationStub::class,
            $container->make(ContainerTestContextInjectOne::class)->impl
        );

        $this->assertInstanceOf(
            ContainerContextImplementationStubTwo::class,
            $container->make(ContainerTestContextInjectTwo::class)->impl
        );

        $this->assertInstanceOf(
            ContainerContextImplementationStubTwo::class,
            $container->make(ContainerTestContextInjectThree::class)->impl
        );
    }

    public function testContextualBindingDoesntOverrideNonContextualResolution()
    {
        $container = new Container;

        $container->instance('stub', new ContainerContextImplementationStub);
        $container->alias('stub', IContainerContextContractStub::class);

        $container->when(ContainerTestContextInjectTwo::class)->needs(IContainerContextContractStub::class)->give(ContainerContextImplementationStubTwo::class);

        $this->assertInstanceOf(
            ContainerContextImplementationStubTwo::class,
            $container->make(ContainerTestContextInjectTwo::class)->impl
        );

        $this->assertInstanceOf(
            ContainerContextImplementationStub::class,
            $container->make(ContainerTestContextInjectOne::class)->impl
        );
    }

    public function testContextuallyBoundInstancesAreNotUnnecessarilyRecreated()
    {
        ContainerTestContextInjectInstantiations::$instantiations = 0;

        $container = new Container;

        $container->instance(IContainerContextContractStub::class, new ContainerImplementationStub);
        $container->instance(ContainerTestContextInjectInstantiations::class, new ContainerTestContextInjectInstantiations);

        $this->assertEquals(1, ContainerTestContextInjectInstantiations::$instantiations);

        $container->when(ContainerTestContextInjectOne::class)->needs(IContainerContextContractStub::class)->give(ContainerTestContextInjectInstantiations::class);

        $container->make(ContainerTestContextInjectOne::class);
        $container->make(ContainerTestContextInjectOne::class);
        $container->make(ContainerTestContextInjectOne::class);
        $container->make(ContainerTestContextInjectOne::class);

        $this->assertEquals(1, ContainerTestContextInjectInstantiations::$instantiations);
    }

    public function testContainerCanInjectSimpleVariable()
    {
        $container = new Container;
        $container->when(ContainerInjectVariableStub::class)->needs('$something')->give(100);
        $instance = $container->make(ContainerInjectVariableStub::class);
        $this->assertEquals(100, $instance->something);

        $container = new Container;
        $container->when(ContainerInjectVariableStub::class)->needs('$something')->give(function ($container) {
            return $container->make(ContainerConcreteStub::class);
        });
        $instance = $container->make(ContainerInjectVariableStub::class);
        $this->assertInstanceOf(ContainerConcreteStub::class, $instance->something);
    }

    public function testContextualBindingWorksWithAliasedTargets()
    {
        $container = new Container;

        $container->bind(IContainerContextContractStub::class, ContainerContextImplementationStub::class);
        $container->alias(IContainerContextContractStub::class, 'interface-stub');

        $container->alias(ContainerContextImplementationStub::class, 'stub-1');

        $container->when(ContainerTestContextInjectOne::class)->needs('interface-stub')->give('stub-1');
        $container->when(ContainerTestContextInjectTwo::class)->needs('interface-stub')->give(ContainerContextImplementationStubTwo::class);

        $one = $container->make(ContainerTestContextInjectOne::class);
        $two = $container->make(ContainerTestContextInjectTwo::class);

        $this->assertInstanceOf(ContainerContextImplementationStub::class, $one->impl);
        $this->assertInstanceOf(ContainerContextImplementationStubTwo::class, $two->impl);
    }

    public function testContextualBindingWorksForNestedOptionalDependencies()
    {
        $container = new Container;

        $container->when(ContainerTestContextInjectTwoInstances::class)->needs(ContainerTestContextInjectTwo::class)->give(function () {
            return new ContainerTestContextInjectTwo(new ContainerContextImplementationStubTwo);
        });

        $resolvedInstance = $container->make(ContainerTestContextInjectTwoInstances::class);
        $this->assertInstanceOf(
            ContainerTestContextWithOptionalInnerDependency::class,
            $resolvedInstance->implOne
        );
        $this->assertNull($resolvedInstance->implOne->inner);

        $this->assertInstanceOf(
            ContainerTestContextInjectTwo::class,
            $resolvedInstance->implTwo
        );
        $this->assertInstanceOf(ContainerContextImplementationStubTwo::class, $resolvedInstance->implTwo->impl);
    }

    public function testContextualBindingWorksForVariadicDependencies()
    {
        $container = new Container;

        $container->when(ContainerTestContextInjectVariadic::class)->needs(IContainerContextContractStub::class)->give(function ($c) {
            return [
                $c->make(ContainerContextImplementationStub::class),
                $c->make(ContainerContextImplementationStubTwo::class),
            ];
        });

        $resolvedInstance = $container->make(ContainerTestContextInjectVariadic::class);

        $this->assertCount(2, $resolvedInstance->stubs);
        $this->assertInstanceOf(ContainerContextImplementationStub::class, $resolvedInstance->stubs[0]);
        $this->assertInstanceOf(ContainerContextImplementationStubTwo::class, $resolvedInstance->stubs[1]);
    }

    public function testContextualBindingWorksForVariadicDependenciesWithNothingBound()
    {
        $container = new Container;

        $resolvedInstance = $container->make(ContainerTestContextInjectVariadic::class);

        $this->assertCount(0, $resolvedInstance->stubs);
    }

    public function testContextualBindingWorksForVariadicAfterNonVariadicDependencies()
    {
        $container = new Container;

        $container->when(ContainerTestContextInjectVariadicAfterNonVariadic::class)->needs(IContainerContextContractStub::class)->give(function ($c) {
            return [
                $c->make(ContainerContextImplementationStub::class),
                $c->make(ContainerContextImplementationStubTwo::class),
            ];
        });

        $resolvedInstance = $container->make(ContainerTestContextInjectVariadicAfterNonVariadic::class);

        $this->assertCount(2, $resolvedInstance->stubs);
        $this->assertInstanceOf(ContainerContextImplementationStub::class, $resolvedInstance->stubs[0]);
        $this->assertInstanceOf(ContainerContextImplementationStubTwo::class, $resolvedInstance->stubs[1]);
    }

    public function testContextualBindingWorksForVariadicAfterNonVariadicDependenciesWithNothingBound()
    {
        $container = new Container;

        $resolvedInstance = $container->make(ContainerTestContextInjectVariadicAfterNonVariadic::class);

        $this->assertCount(0, $resolvedInstance->stubs);
    }

    public function testContextualBindingWorksForVariadicDependenciesWithoutFactory()
    {
        $container = new Container;

        $container->when(ContainerTestContextInjectVariadic::class)->needs(IContainerContextContractStub::class)->give([
            ContainerContextImplementationStub::class,
            ContainerContextImplementationStubTwo::class,
        ]);

        $resolvedInstance = $container->make(ContainerTestContextInjectVariadic::class);

        $this->assertCount(2, $resolvedInstance->stubs);
        $this->assertInstanceOf(ContainerContextImplementationStub::class, $resolvedInstance->stubs[0]);
        $this->assertInstanceOf(ContainerContextImplementationStubTwo::class, $resolvedInstance->stubs[1]);
    }

    public function testContextualBindingGivesTagsForArrayWithNoTagsDefined()
    {
        $container = new Container;

        $container->when(ContainerTestContextInjectArray::class)->needs('$stubs')->giveTagged('stub');

        $resolvedInstance = $container->make(ContainerTestContextInjectArray::class);

        $this->assertCount(0, $resolvedInstance->stubs);
    }

    public function testContextualBindingGivesTagsForVariadicWithNoTagsDefined()
    {
        $container = new Container;

        $container->when(ContainerTestContextInjectVariadic::class)->needs(IContainerContextContractStub::class)->giveTagged('stub');

        $resolvedInstance = $container->make(ContainerTestContextInjectVariadic::class);

        $this->assertCount(0, $resolvedInstance->stubs);
    }

    public function testContextualBindingGivesTagsForArray()
    {
        $container = new Container;

        $container->tag([
            ContainerContextImplementationStub::class,
            ContainerContextImplementationStubTwo::class,
        ], ['stub']);

        $container->when(ContainerTestContextInjectArray::class)->needs('$stubs')->giveTagged('stub');

        $resolvedInstance = $container->make(ContainerTestContextInjectArray::class);

        $this->assertCount(2, $resolvedInstance->stubs);
        $this->assertInstanceOf(ContainerContextImplementationStub::class, $resolvedInstance->stubs[0]);
        $this->assertInstanceOf(ContainerContextImplementationStubTwo::class, $resolvedInstance->stubs[1]);
    }

    public function testContextualBindingGivesTagsForVariadic()
    {
        $container = new Container;

        $container->tag([
            ContainerContextImplementationStub::class,
            ContainerContextImplementationStubTwo::class,
        ], ['stub']);

        $container->when(ContainerTestContextInjectVariadic::class)->needs(IContainerContextContractStub::class)->giveTagged('stub');

        $resolvedInstance = $container->make(ContainerTestContextInjectVariadic::class);

        $this->assertCount(2, $resolvedInstance->stubs);
        $this->assertInstanceOf(ContainerContextImplementationStub::class, $resolvedInstance->stubs[0]);
        $this->assertInstanceOf(ContainerContextImplementationStubTwo::class, $resolvedInstance->stubs[1]);
    }

    public function testContextualBindingGivesValuesFromConfigOptionalValueNull()
    {
        $container = new Container;

        $container->singleton('config', function () {
            return new Repository([
                'test' => [
                    'username' => 'laravel',
                    'password' => 'hunter42',
                ],
            ]);
        });

        $container
            ->when(ContainerTestContextInjectFromConfigIndividualValues::class)
            ->needs('$username')
            ->giveConfig('test.username');

        $container
            ->when(ContainerTestContextInjectFromConfigIndividualValues::class)
            ->needs('$password')
            ->giveConfig('test.password');

        $resolvedInstance = $container->make(ContainerTestContextInjectFromConfigIndividualValues::class);

        $this->assertSame('laravel', $resolvedInstance->username);
        $this->assertSame('hunter42', $resolvedInstance->password);
        $this->assertNull($resolvedInstance->alias);
    }

    public function testContextualBindingGivesValuesFromConfigOptionalValueSet()
    {
        $container = new Container;

        $container->singleton('config', function () {
            return new Repository([
                'test' => [
                    'username' => 'laravel',
                    'password' => 'hunter42',
                    'alias' => 'lumen',
                ],
            ]);
        });

        $container
            ->when(ContainerTestContextInjectFromConfigIndividualValues::class)
            ->needs('$username')
            ->giveConfig('test.username');

        $container
            ->when(ContainerTestContextInjectFromConfigIndividualValues::class)
            ->needs('$password')
            ->giveConfig('test.password');

        $container
            ->when(ContainerTestContextInjectFromConfigIndividualValues::class)
            ->needs('$alias')
            ->giveConfig('test.alias');

        $resolvedInstance = $container->make(ContainerTestContextInjectFromConfigIndividualValues::class);

        $this->assertSame('laravel', $resolvedInstance->username);
        $this->assertSame('hunter42', $resolvedInstance->password);
        $this->assertSame('lumen', $resolvedInstance->alias);
    }

    public function testContextualBindingGivesValuesFromConfigWithDefault()
    {
        $container = new Container;

        $container->singleton('config', function () {
            return new Repository([
                'test' => [
                    'password' => 'hunter42',
                ],
            ]);
        });

        $container
            ->when(ContainerTestContextInjectFromConfigIndividualValues::class)
            ->needs('$username')
            ->giveConfig('test.username', 'DEFAULT_USERNAME');

        $container
            ->when(ContainerTestContextInjectFromConfigIndividualValues::class)
            ->needs('$password')
            ->giveConfig('test.password');

        $resolvedInstance = $container->make(ContainerTestContextInjectFromConfigIndividualValues::class);

        $this->assertSame('DEFAULT_USERNAME', $resolvedInstance->username);
        $this->assertSame('hunter42', $resolvedInstance->password);
        $this->assertNull($resolvedInstance->alias);
    }

    public function testContextualBindingGivesValuesFromConfigArray()
    {
        $container = new Container;

        $container->singleton('config', function () {
            return new Repository([
                'test' => [
                    'username' => 'laravel',
                    'password' => 'hunter42',
                    'alias' => 'lumen',
                ],
            ]);
        });

        $container
            ->when(ContainerTestContextInjectFromConfigArray::class)
            ->needs('$settings')
            ->giveConfig('test');

        $resolvedInstance = $container->make(ContainerTestContextInjectFromConfigArray::class);

        $this->assertSame('laravel', $resolvedInstance->settings['username']);
        $this->assertSame('hunter42', $resolvedInstance->settings['password']);
        $this->assertSame('lumen', $resolvedInstance->settings['alias']);
    }

    public function testContextualBindingWorksForMethodInvocation()
    {
        $container = new Container;

        $container
            ->when(ContainerTestContextInjectMethodArgument::class)
            ->needs(IContainerContextContractStub::class)
            ->give(ContainerContextImplementationStub::class);

        $object = new ContainerTestContextInjectMethodArgument;

        // array callable syntax...
        $valueResolvedUsingArraySyntax = $container->call([$object, 'method']);
        $this->assertInstanceOf(ContainerContextImplementationStub::class, $valueResolvedUsingArraySyntax);

        // first class callable syntax...
        $valueResolvedUsingFirstClassSyntax = $container->call($object->method(...));
        $this->assertInstanceOf(ContainerContextImplementationStub::class, $valueResolvedUsingFirstClassSyntax);
    }
}

interface IContainerContextContractStub
{
    //
}

class ContainerContextNonContractStub
{
    //
}

class ContainerContextImplementationStub implements IContainerContextContractStub
{
    //
}

class ContainerContextImplementationStubTwo implements IContainerContextContractStub
{
    //
}

class ContainerTestContextInjectInstantiations implements IContainerContextContractStub
{
    public static $instantiations;

    public function __construct()
    {
        static::$instantiations++;
    }
}

class ContainerTestContextInjectOne
{
    public $impl;

    public function __construct(IContainerContextContractStub $impl)
    {
        $this->impl = $impl;
    }
}

class ContainerTestContextInjectTwo
{
    public $impl;

    public function __construct(IContainerContextContractStub $impl)
    {
        $this->impl = $impl;
    }
}

class ContainerTestContextInjectThree
{
    public $impl;

    public function __construct(IContainerContextContractStub $impl)
    {
        $this->impl = $impl;
    }
}

class ContainerTestContextInjectTwoInstances
{
    public $implOne;
    public $implTwo;

    public function __construct(ContainerTestContextWithOptionalInnerDependency $implOne, ContainerTestContextInjectTwo $implTwo)
    {
        $this->implOne = $implOne;
        $this->implTwo = $implTwo;
    }
}

class ContainerTestContextWithOptionalInnerDependency
{
    public $inner;

    public function __construct(?ContainerTestContextInjectOne $inner = null)
    {
        $this->inner = $inner;
    }
}

class ContainerTestContextInjectArray
{
    public $stubs;

    public function __construct(array $stubs)
    {
        $this->stubs = $stubs;
    }
}

class ContainerTestContextInjectVariadic
{
    public $stubs;

    public function __construct(IContainerContextContractStub ...$stubs)
    {
        $this->stubs = $stubs;
    }
}

class ContainerTestContextInjectVariadicAfterNonVariadic
{
    public $other;
    public $stubs;

    public function __construct(ContainerContextNonContractStub $other, IContainerContextContractStub ...$stubs)
    {
        $this->other = $other;
        $this->stubs = $stubs;
    }
}

class ContainerTestContextInjectFromConfigIndividualValues
{
    public $username;
    public $password;
    public $alias = null;

    public function __construct($username, $password, $alias = null)
    {
        $this->username = $username;
        $this->password = $password;
        $this->alias = $alias;
    }
}

class ContainerTestContextInjectFromConfigArray
{
    public $settings;

    public function __construct($settings)
    {
        $this->settings = $settings;
    }
}

class ContainerTestContextInjectMethodArgument
{
    public function method(IContainerContextContractStub $dependency)
    {
        return $dependency;
    }
}
