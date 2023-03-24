<?php

namespace Illuminate\Tests\Config;

use Illuminate\Config\Attributes\InjectedConfig;
use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class InjectedConfigTest extends TestCase
{
    protected function setUp(): void
    {
        $container = Container::setInstance(new Container);

        $container->singleton('config', function () {
            return new Repository($this->createConfig());
        });
    }

    protected function tearDown(): void
    {
        m::close();

        Container::setInstance(null);
    }

    protected function createConfig()
    {
        return [
            'foo.string' => 'Laravel',
            'foo.boolean' => true,
            'foo.int' => 1,
            'foo.float' => 1.1,
            'foo.array' => [],

            'foo.nullable.string' => null,
            'foo.nullable.boolean' => null,
            'foo.nullable.int' => null,
            'foo.nullable.float' => null,
            'foo.nullable.array' => null,

            'foo.nullable_value.string' => 'Taylor Otwell',
            'foo.nullable_value.boolean' => false,
            'foo.nullable_value.int' => 123,
            'foo.nullable_value.float' => 3.14,
            'foo.nullable_value.array' => ['a' => 'b'],
        ];
    }

    public function testItInjectsValuesForInjectedConfigAttributedProperties(): void
    {
        $testClass = Container::getInstance()->get(ConfigInjectionTestClass::class);

        $this->assertEquals('Laravel', $testClass->fooString);
        $this->assertEquals(true, $testClass->fooBoolean);
        $this->assertEquals(1, $testClass->fooInt);
        $this->assertEquals(1.1, $testClass->fooFloat);
        $this->assertEquals([], $testClass->fooArray);

        $this->assertNull($testClass->fooNullableString);
        $this->assertNull($testClass->fooNullableBoolean);
        $this->assertNull($testClass->fooNullableInt);
        $this->assertNull($testClass->fooNullableFloat);
        $this->assertNull($testClass->fooNullableArray);

        $this->assertEquals('Taylor Otwell', $testClass->fooNullableValueString);
        $this->assertEquals(false, $testClass->fooNullableValueBoolean);
        $this->assertEquals(123, $testClass->fooNullableValueInt);
        $this->assertEquals(3.14, $testClass->fooNullableValueFloat);
        $this->assertSame(['a' => 'b'], $testClass->fooNullableValueArray);
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

        #[InjectedConfig('foo.nullable_value.string')]
        public ?string $fooNullableValueString,
        #[InjectedConfig('foo.nullable_value.boolean')]
        public ?bool $fooNullableValueBoolean,
        #[InjectedConfig('foo.nullable_value.int')]
        public ?int $fooNullableValueInt,
        #[InjectedConfig('foo.nullable_value.float')]
        public ?float $fooNullableValueFloat,
        #[InjectedConfig('foo.nullable_value.array')]
        public ?array $fooNullableValueArray,
    ) {
    }
}
