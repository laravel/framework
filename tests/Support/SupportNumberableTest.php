<?php

namespace Illuminate\Tests\Support;

use Illuminate\Support\Number;
use Illuminate\Support\Numberable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class SupportNumberableTest extends TestCase
{
    /**
     * @param  int|float|string  $number
     * @return \Illuminate\Support\Numberable
     */
    protected function numberable($number = 0)
    {
        return new Numberable($number);
    }

    public function testCreation()
    {
        $this->assertSame(42, $this->numberable(42)->value());
        $this->assertSame(42.5, $this->numberable(42.5)->value());
        $this->assertSame(42, $this->numberable('42')->value());
        $this->assertSame(42.5, $this->numberable('42.5')->value());
        $this->assertSame(0, $this->numberable()->value());
    }

    public function testCreationWithInvalidString()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid numeric value: not-a-number');

        $this->numberable('not-a-number');
    }

    public function testNumberOf()
    {
        $this->assertInstanceOf(Numberable::class, Number::of(42));
        $this->assertSame(42, Number::of(42)->value());
    }

    public function testAbs()
    {
        $this->assertSame(5, $this->numberable(-5)->abs()->value());
        $this->assertSame(5, $this->numberable(5)->abs()->value());
        $this->assertSame(0, $this->numberable(0)->abs()->value());
    }

    public function testRound()
    {
        $this->assertSame(3.0, $this->numberable(3.14159)->round()->value());
        $this->assertSame(3.14, $this->numberable(3.14159)->round(2)->value());
        $this->assertSame(3.142, $this->numberable(3.14159)->round(3)->value());
    }

    public function testCeil()
    {
        $this->assertSame(4.0, $this->numberable(3.14159)->ceil()->value());
        $this->assertSame(3.0, $this->numberable(3.0)->ceil()->value());
        $this->assertSame(-3.0, $this->numberable(-3.14159)->ceil()->value());
    }

    public function testFloor()
    {
        $this->assertSame(3.0, $this->numberable(3.14159)->floor()->value());
        $this->assertSame(3.0, $this->numberable(3.0)->floor()->value());
        $this->assertSame(-4.0, $this->numberable(-3.14159)->floor()->value());
    }

    public function testClamp()
    {
        $this->assertSame(2, $this->numberable(1)->clamp(2, 3)->value());
        $this->assertSame(3, $this->numberable(5)->clamp(2, 3)->value());
        $this->assertSame(5, $this->numberable(5)->clamp(1, 10)->value());
    }

    public function testPower()
    {
        $this->assertSame(8, $this->numberable(2)->power(3)->value());
        $this->assertSame(9, $this->numberable(3)->power(2)->value());
        $this->assertSame(1, $this->numberable(5)->power(0)->value());
    }

    public function testSqrt()
    {
        $this->assertSame(2.0, $this->numberable(4)->sqrt()->value());
        $this->assertSame(3.0, $this->numberable(9)->sqrt()->value());
        $this->assertSame(5.0, $this->numberable(25)->sqrt()->value());
    }

    public function testTrim()
    {
        $this->assertSame(12, $this->numberable(12.0)->trim()->value());
        $this->assertSame(12.3, $this->numberable(12.30)->trim()->value());
    }

    public function testIsEven()
    {
        $this->assertTrue($this->numberable(0)->isEven());
        $this->assertTrue($this->numberable(2)->isEven());
        $this->assertTrue($this->numberable(4)->isEven());
        $this->assertFalse($this->numberable(1)->isEven());
        $this->assertFalse($this->numberable(3)->isEven());
    }

    public function testIsOdd()
    {
        $this->assertTrue($this->numberable(1)->isOdd());
        $this->assertTrue($this->numberable(3)->isOdd());
        $this->assertTrue($this->numberable(5)->isOdd());
        $this->assertFalse($this->numberable(0)->isOdd());
        $this->assertFalse($this->numberable(2)->isOdd());
    }

    public function testIsPrime()
    {
        $this->assertFalse($this->numberable(0)->isPrime());
        $this->assertFalse($this->numberable(1)->isPrime());
        $this->assertTrue($this->numberable(2)->isPrime());
        $this->assertTrue($this->numberable(3)->isPrime());
        $this->assertFalse($this->numberable(4)->isPrime());
        $this->assertTrue($this->numberable(5)->isPrime());
        $this->assertTrue($this->numberable(17)->isPrime());
        $this->assertFalse($this->numberable(25)->isPrime());
    }

    public function testIsPerfectSquare()
    {
        $this->assertTrue($this->numberable(0)->isPerfectSquare());
        $this->assertTrue($this->numberable(1)->isPerfectSquare());
        $this->assertTrue($this->numberable(4)->isPerfectSquare());
        $this->assertTrue($this->numberable(9)->isPerfectSquare());
        $this->assertTrue($this->numberable(16)->isPerfectSquare());
        $this->assertFalse($this->numberable(2)->isPerfectSquare());
        $this->assertFalse($this->numberable(3)->isPerfectSquare());
        $this->assertFalse($this->numberable(5)->isPerfectSquare());
    }

    public function testFactorial()
    {
        $this->assertSame(1, $this->numberable(0)->factorial()->value());
        $this->assertSame(1, $this->numberable(1)->factorial()->value());
        $this->assertSame(2, $this->numberable(2)->factorial()->value());
        $this->assertSame(6, $this->numberable(3)->factorial()->value());
        $this->assertSame(24, $this->numberable(4)->factorial()->value());
        $this->assertSame(120, $this->numberable(5)->factorial()->value());
    }

    public function testGcd()
    {
        $this->assertSame(6, $this->numberable(18)->gcd(24)->value());
        $this->assertSame(1, $this->numberable(17)->gcd(13)->value());
        $this->assertSame(15, $this->numberable(45)->gcd(60)->value());
    }

    public function testLcm()
    {
        $this->assertSame(12, $this->numberable(4)->lcm(6)->value());
        $this->assertSame(15, $this->numberable(3)->lcm(5)->value());
        $this->assertSame(36, $this->numberable(9)->lcm(12)->value());
    }

    public function testArithmeticOperations()
    {
        $this->assertSame(7, $this->numberable(5)->add(2)->value());
        $this->assertSame(3, $this->numberable(5)->subtract(2)->value());
        $this->assertSame(10, $this->numberable(5)->multiply(2)->value());
        $this->assertSame(2.5, $this->numberable(5)->divide(2)->value());
        $this->assertSame(1, $this->numberable(5)->modulus(2)->value());
    }

    public function testComparisonMethods()
    {
        $this->assertTrue($this->numberable(5)->equals(5));
        $this->assertFalse($this->numberable(5)->equals(4));

        $this->assertTrue($this->numberable(5)->greaterThan(4));
        $this->assertFalse($this->numberable(5)->greaterThan(6));

        $this->assertTrue($this->numberable(5)->greaterThanOrEqual(5));
        $this->assertTrue($this->numberable(5)->greaterThanOrEqual(4));
        $this->assertFalse($this->numberable(5)->greaterThanOrEqual(6));

        $this->assertTrue($this->numberable(5)->lessThan(6));
        $this->assertFalse($this->numberable(5)->lessThan(4));

        $this->assertTrue($this->numberable(5)->lessThanOrEqual(5));
        $this->assertTrue($this->numberable(5)->lessThanOrEqual(6));
        $this->assertFalse($this->numberable(5)->lessThanOrEqual(4));

        $this->assertTrue($this->numberable(5)->between(4, 6));
        $this->assertTrue($this->numberable(5)->between(5, 5));
        $this->assertFalse($this->numberable(5)->between(6, 8));
    }

    public function testChaining()
    {
        $result = $this->numberable(10)
            ->add(5)
            ->multiply(2)
            ->subtract(10)
            ->divide(2);

        $this->assertSame(10, $result->value());
    }

    public function testFormat()
    {
        $this->assertSame('1,234', $this->numberable(1234)->format());
        $this->assertSame('1,234.56', $this->numberable(1234.56)->format(2));
    }

    public function testSpell()
    {
        $this->assertSame('ten', $this->numberable(10)->spell());
        $this->assertSame('one point two', $this->numberable(1.2)->spell());
    }

    public function testOrdinal()
    {
        $this->assertSame('1st', $this->numberable(1)->ordinal());
        $this->assertSame('2nd', $this->numberable(2)->ordinal());
        $this->assertSame('3rd', $this->numberable(3)->ordinal());
    }

    public function testForHumans()
    {
        $this->assertSame('1 thousand', $this->numberable(1000)->forHumans());
        $this->assertSame('1 million', $this->numberable(1000000)->forHumans());
    }

    public function testAbbreviate()
    {
        $this->assertSame('1K', $this->numberable(1000)->abbreviate());
        $this->assertSame('1M', $this->numberable(1000000)->abbreviate());
    }

    public function testFileSize()
    {
        $this->assertSame('1 KB', $this->numberable(1024)->fileSize());
        $this->assertSame('1 MB', $this->numberable(1024 * 1024)->fileSize());
    }

    public function testPercentage()
    {
        $this->assertSame('50%', $this->numberable(50)->percentage());
        $this->assertSame('50.00%', $this->numberable(50)->percentage(2));
    }

    public function testCurrency()
    {
        $this->assertSame('$100.00', $this->numberable(100)->currency());
        $this->assertSame('€100.00', $this->numberable(100)->currency('EUR'));
    }

    public function testToString()
    {
        $this->assertSame('42', (string) $this->numberable(42));
        $this->assertSame('42.5', (string) $this->numberable(42.5));
    }

    public function testJsonSerialize()
    {
        $this->assertSame(42, $this->numberable(42)->jsonSerialize());
        $this->assertSame(42.5, $this->numberable(42.5)->jsonSerialize());
    }

    public function testNumberHelperFunction()
    {
        $this->assertInstanceOf(Numberable::class, number(42));
        $this->assertSame(42, number(42)->value());

        // Test static accessor when called without arguments
        $staticAccessor = number();
        $this->assertSame('1,234', $staticAccessor->format(1234));
        $this->assertSame('0', (string) $staticAccessor);
    }
}
