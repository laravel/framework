<?php

namespace Illuminate\Tests\Config;

use Illuminate\Config\Attributes\InjectedConfig;
use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class InjectedConfigTest extends TestCase
{
    private function mockConfig(Repository $mock, $key, $default, $expectedValue)
    {
        return $mock->allows('get')
            ->with($key, $default)
            ->andReturns($expectedValue)
            ->getMock();
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testItInjectsValuesForInjectedConfigAttributedProperties(): void
    {
        $container = new Container();
        $mock = m::mock(Repository::class);
        $mock = $this->mockConfig($mock, 'foo.string', null, 'Laravel');
        $mock = $this->mockConfig($mock, 'foo.boolean', null, true);
        $mock = $this->mockConfig($mock, 'foo.int', null, 1);
        $mock = $this->mockConfig($mock, 'foo.float', null, 1.1);
        $mock = $this->mockConfig($mock, 'foo.array', null, []);

        $mock = $this->mockConfig($mock, 'foo.nullable.string', null, null);
        $mock = $this->mockConfig($mock, 'foo.nullable.boolean', null, null);
        $mock = $this->mockConfig($mock, 'foo.nullable.int', null, null);
        $mock = $this->mockConfig($mock, 'foo.nullable.float', null, null);
        $mock = $this->mockConfig($mock, 'foo.nullable.array', null, null);

        $mock = $this->mockConfig($mock, 'foo.missing.string', 'default', 'default');
        $mock = $this->mockConfig($mock, 'foo.missing.boolean', true, true);
        $mock = $this->mockConfig($mock, 'foo.missing.int', 1, 1);
        $mock = $this->mockConfig($mock, 'foo.missing.float', 1.0, 1.0);
        $mock = $this->mockConfig($mock, 'foo.missing.array', [], []);

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

        self::assertEquals('default', $testClass->fooDefaultString);
        self::assertEquals(true, $testClass->fooDefaultBoolean);
        self::assertEquals(1, $testClass->fooDefaultInt);
        self::assertEquals(1.0, $testClass->fooDefaultFloat);
        self::assertEquals([], $testClass->fooDefaultArray);
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

        #[InjectedConfig('foo.missing.string', 'default')]
        public ?string $fooDefaultString,
        #[InjectedConfig('foo.missing.boolean', true)]
        public ?bool $fooDefaultBoolean,
        #[InjectedConfig('foo.missing.int', 1)]
        public ?int $fooDefaultInt,
        #[InjectedConfig('foo.missing.float', 1.0)]
        public ?float $fooDefaultFloat,
        #[InjectedConfig('foo.missing.array', [])]
        public ?array $fooDefaultArray,
    ) {
    }
}
