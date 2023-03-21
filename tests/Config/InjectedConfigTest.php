<?php

namespace Illuminate\Tests\Config;

use Illuminate\Config\Attributes\InjectedConfig;
use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class InjectedConfigTest extends TestCase
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testItInjectsValuesForInjectedConfigAttributedProperties(): void
    {
        $config = [
            'foo.string'=> 'Laravel',
            'foo.boolean'=> true,
            'foo.int'=> 1,
            'foo.float'=> 1.1,
            'foo.array'=> [],

            'foo.nullable.string'=> null,
            'foo.nullable.boolean'=> null,
            'foo.nullable.int'=> null,
            'foo.nullable.float'=> null,
            'foo.nullable.array'=> null,
        ];

        $container = new Container();
        $container->instance('config', new Repository($config));
        $testClass = $container->get(ConfigInjectionTestClass::class);

        $this->assertEquals('Laravel', $testClass->fooString);
        $this->assertEquals(true, $testClass->fooBoolean);
        $this->assertEquals(1, $testClass->fooInt);
        $this->assertEquals(1.1, $testClass->fooFloat);
        $this->assertEquals([], $testClass->fooArray);
        $this->assertEquals(null, $testClass->fooNull);

        $this->assertEquals(null, $testClass->fooNullableString);
        $this->assertEquals(null, $testClass->fooNullableBoolean);
        $this->assertEquals(null, $testClass->fooNullableInt);
        $this->assertEquals(null, $testClass->fooNullableFloat);
        $this->assertEquals(null, $testClass->fooNullableArray);
    }
}

class ConfigInjectionTestClass
{
    public function __construct(
        #[InjectedConfig('foo.string')]
        public string $fooString,
        #[InjectedConfig('foo.boolean')]
        public string $fooBoolean,
        #[InjectedConfig('foo.int')]
        public int $fooInt,
        #[InjectedConfig('foo.float')]
        public float $fooFloat,
        #[InjectedConfig('foo.array')]
        public array $fooArray,

        #[InjectedConfig('foo.nullable.string')]
        public ?string $fooNullableString,
        #[InjectedConfig('foo.nullable.boolean')]
        public ?bool $fooNullableBoolean,
        #[InjectedConfig('foo.nullable.int')]
        public ?int $fooNullableInt,
        #[InjectedConfig('foo.nullable.float')]
        public ?float $fooNullableFloat,
        #[InjectedConfig('foo.nullable.array')]
        public ?array $fooNullableArray,
    ) {
    }
}
