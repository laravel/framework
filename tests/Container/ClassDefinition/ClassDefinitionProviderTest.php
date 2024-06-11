<?php

declare(strict_types=1);

namespace Illuminate\Tests\Container\ClassDefinition;

use Illuminate\Container\ClassDefinition\ClassDefinition;
use Illuminate\Container\ClassDefinition\ClassDefinitionProvider;
use Illuminate\Container\ClassDefinition\Parameter;
use PHPUnit\Framework\TestCase;

final class ClassDefinitionProviderTest extends TestCase
{
    public function testReturnsAProperDefinitionOfABasicClass(): void
    {
        $sut = new ClassDefinitionProvider();

        $definition = $sut->get(Service::class);

        $this->assertEquals(new ClassDefinition(
            class: Service::class,
            isInstantiable: true,
            isConstructorDefined: true,
            parameters: [
                new Parameter(
                    name: 'service1',
                    className: Service1::class,
                    isVariadic: false,
                    isDefaultValueAvailable: false,
                    defaultValue: null,
                    declaringClassName: Service::class,
                    asString: 'Parameter #0 [ <required> Illuminate\Tests\Container\ClassDefinition\Service1 $service1 ]',
                ),
                new Parameter(
                    name: 'service2',
                    className: Service2::class,
                    isVariadic: false,
                    isDefaultValueAvailable: false,
                    defaultValue: null,
                    declaringClassName: Service::class,
                    asString: 'Parameter #1 [ <required> Illuminate\Tests\Container\ClassDefinition\Service2 $service2 ]',
                ),
                new Parameter(
                    name: 'primitive',
                    className: null,
                    isVariadic: false,
                    isDefaultValueAvailable: false,
                    defaultValue: null,
                    declaringClassName: Service::class,
                    asString: 'Parameter #2 [ <required> string $primitive ]',
                ),
            ],
        ), $definition);
    }

    public function testReturnsAProperDefinitionOfAClassWithVariadicParameters(): void
    {
        $sut = new ClassDefinitionProvider();

        $definition = $sut->get(ServiceVariadicParameters::class);

        $this->assertEquals(new ClassDefinition(
            class: ServiceVariadicParameters::class,
            isInstantiable: true,
            isConstructorDefined: true,
            parameters: [
                new Parameter(
                    name: 'params',
                    className: Param::class,
                    isVariadic: true,
                    isDefaultValueAvailable: false,
                    defaultValue: null,
                    declaringClassName: ServiceVariadicParameters::class,
                    asString: 'Parameter #0 [ <optional> Illuminate\Tests\Container\ClassDefinition\Param ...$params ]',
                ),
            ],
        ), $definition);
    }
}

class Service
{
    public function __construct(
        private Service1 $service1,
        private Service2 $service2,
        private string $primitive,
    ) {
    }
}

class Service1
{
}

class Service2
{
}

class ServiceVariadicParameters
{
    public function __construct(
        Param ...$params,
    ) {
    }
}

class Param
{
}
