<?php

namespace Illuminate\Tests\Config;

use Illuminate\Config\Attributes\InjectedConfig;
use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class InjectedConfigTest extends TestCase
{
    private function mockConfigInjection(Repository $mock, $key, $expectedValue): m\LegacyMockInterface|m\MockInterface|Repository|null
    {
        return $mock->allows('get')
            ->with($key)
            ->andReturns($expectedValue)
            ->getMock();
    }

    public function testItInjectsValuesForInjectedConfigAttibutedProperties(): void
    {
        $container = new Container();
        $mock = m::mock(Repository::class);
        $mock = $this->mockConfigInjection($mock, 'foo.string', 'Laravel');
        $mock = $this->mockConfigInjection($mock, 'foo.boolean', true);
        $mock = $this->mockConfigInjection($mock, 'foo.int', 1);
        $mock = $this->mockConfigInjection($mock, 'foo.float', 1.1);
        $mock = $this->mockConfigInjection($mock, 'foo.array', []);

        $mock = $this->mockConfigInjection($mock, 'foo.nullable.string', null);
        $mock = $this->mockConfigInjection($mock, 'foo.nullable.boolean', null);
        $mock = $this->mockConfigInjection($mock, 'foo.nullable.int', null);
        $mock = $this->mockConfigInjection($mock, 'foo.nullable.float', null);
        $mock = $this->mockConfigInjection($mock, 'foo.nullable.array', null);


        $container->instance(Repository::class, $mock);
        $container->bind('config', Repository::class);
        $testClass = $container->get(ConfigInjectionTestClass::class);

        self::assertEquals('Laravel', $testClass->fooString);
        self::assertEquals(true, $testClass->fooBoolean);
        self::assertEquals(1, $testClass->fooInt);
        self::assertEquals(1.1, $testClass->fooFloat);
        self::assertEquals([], $testClass->fooArray);
        self::assertEquals(null, $testClass->fooNull);

        self::assertEquals(null, $testClass->fooNullableString);
        self::assertEquals(null, $testClass->fooNullableBoolean);
        self::assertEquals(null, $testClass->fooNullableInt);
        self::assertEquals(null, $testClass->fooNullableFloat);
        self::assertEquals(null, $testClass->fooNullableArray);
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
    )
    {
    }
}
