<?php

namespace Illuminate\Tests\Support;

use BadMethodCallException;
use Illuminate\Support\Traits\MagicalEnum;
use Illuminate\Tests\Support\Fixtures\EnumInterface;
use Illuminate\Tests\Support\Fixtures\ExampleTrait;
use Illuminate\Tests\Support\Fixtures\IntBackedMagicalEnum;
use Illuminate\Tests\Support\Fixtures\MagicalUnitEnum;
use Illuminate\Tests\Support\Fixtures\StringBackedMagicalEnum;
use PHPUnit\Framework\TestCase;

include_once 'Enums.php';

class SupportMagicalEnumTest extends TestCase
{

    public function testIsEnum(): void
    {
        $this->assertTrue(IntBackedMagicalEnum::isEnum());
    }

    public function testIsEnumWhenTraitAddToClass(): void
    {
        $instance = new class {
            use MagicalEnum;
        };

        $this->assertFalse($instance::isEnum());
    }

    public function testIsBackedEnum(): void
    {
        $this->assertTrue(IntBackedMagicalEnum::isBackedEnum());
        $this->assertFalse(MagicalUnitEnum::isBackedEnum());

        $this->expectException(BadMethodCallException::class);
        $instance = new class {
            use MagicalEnum;
        };

        $instance::isBackedEnum();
    }

    public function testCount(): void
    {
        $this->assertEquals(3, IntBackedMagicalEnum::count());
        $this->assertEquals(4, MagicalUnitEnum::count());
    }

    public function testGetBackingType(): void
    {
        $this->assertEquals('int', IntBackedMagicalEnum::getBackingType());
        $this->assertEquals('string', stringBackedMagicalEnum::getBackingType());
        $this->assertNull(MagicalUnitEnum::getBackingType());

        $this->expectException(BadMethodCallException::class);
        $instance = new class {
            use MagicalEnum;
        };

        $instance::getBackingType();
    }

    public function testIsImplementsInterface(): void
    {
        $this->assertTrue(IntBackedMagicalEnum::isImplementsInterface(EnumInterface::class));
        $this->assertFalse(MagicalUnitEnum::isImplementsInterface(EnumInterface::class));
    }

    public function testIsTraitUsed(): void
    {
        $this->assertTrue(IntBackedMagicalEnum::isTraitUsed(ExampleTrait::class));
        $this->assertFalse(StringBackedMagicalEnum::isTraitUsed(ExampleTrait::class));

        $instance = new class {
            use MagicalEnum, ExampleTrait;
        };

        $this->assertTrue($instance::isTraitUsed(ExampleTrait::class));
    }

    public function testNames(): void
    {
        $this->assertEquals(['ONE', 'TOW', 'THREE'], IntBackedMagicalEnum::names());
        $this->assertEquals(['Taylor', 'Laravel'], stringBackedMagicalEnum::names());
        $this->assertEquals(['A', 'B', 'C', 'D'], MagicalUnitEnum::names());

        $this->expectException(BadMethodCallException::class);
        $instance = new class {
            use MagicalEnum;
        };

        $this->assertNotEquals(['ONE', 'TOW', 'THREE'], $instance::names());
    }

    public function testValues(): void
    {
        $this->assertEquals([1, 2, 3], IntBackedMagicalEnum::values());
        $this->assertEquals(['Otwell', 'Framework'], stringBackedMagicalEnum::values());

        $this->assertEquals([], MagicalUnitEnum::values());

        $this->expectException(BadMethodCallException::class);
        $instance = new class {
            use MagicalEnum;
        };
        $this->assertNotEquals(['A', 'B', 'C', 'D'], $instance::names());
    }

    public function testToArray(): void
    {
        $this->assertEquals(['ONE' => 1, 'TOW' => 2, 'THREE' => 3], IntBackedMagicalEnum::toArray());
        $this->assertEquals(['Taylor' => 'Otwell', 'Laravel' => 'Framework'], stringBackedMagicalEnum::toArray());
        $this->assertEquals(['A', 'B', 'C', 'D'], MagicalUnitEnum::toArray());

        $this->expectException(BadMethodCallException::class);
        $instance = new class {
            use MagicalEnum;
        };

        $this->assertNotEquals(['A', 'B', 'C'], $instance::names());
    }

    public function testReverseArray(): void
    {
        $this->assertEquals([1 => 'ONE', 2 => 'TOW', 3 => 'THREE'], IntBackedMagicalEnum::reverseArray());
        $this->assertEquals(['Otwell' => 'Taylor', 'Framework' => 'Laravel'], stringBackedMagicalEnum::reverseArray());
        $this->assertEquals(['A', 'B', 'C', 'D'], MagicalUnitEnum::reverseArray());

        $this->expectException(BadMethodCallException::class);
        $instance = new class {
            use MagicalEnum;
        };

        $this->assertNotEquals(['A', 'B', 'C'], $instance::names());
    }

    public function testHasCase(): void
    {
        $this->assertTrue(IntBackedMagicalEnum::hasCase('ONE'));
        $this->assertFalse(IntBackedMagicalEnum::hasCase('FOUR'));

        $this->assertTrue(StringBackedMagicalEnum::hasCase('Taylor'));
        $this->assertFalse(StringBackedMagicalEnum::hasCase('DotNetCore'));

        $this->assertTrue(MagicalUnitEnum::hasCase('A'));
        $this->assertFalse(MagicalUnitEnum::hasCase('Z'));
    }


    public function testIsValidEnumValue(): void
    {
        $this->assertTrue(IntBackedMagicalEnum::isValidEnumValue(2));
        $this->assertFalse(IntBackedMagicalEnum::isValidEnumValue(100));

        $this->assertTrue(StringBackedMagicalEnum::isValidEnumValue('Framework'));
        $this->assertFalse(StringBackedMagicalEnum::isValidEnumValue('Forge'));

        $this->expectException(BadMethodCallException::class);
        $instance = new class {
            use MagicalEnum;
        };

        $this->assertTrue($instance::isValidEnumValue(2));
    }
}
