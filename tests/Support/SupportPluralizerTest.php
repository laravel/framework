<?php

namespace Illuminate\Tests\Support;

use Illuminate\Support\Str;
use UnexpectedValueException;
use PHPUnit\Framework\TestCase;
use Illuminate\Support\Pluralizer;
use Illuminate\Support\Pluralizers\PluralizerInterface;

class SupportPluralizerTest extends TestCase
{
    public function testBasicSingular()
    {
        $this->assertEquals('child', Str::singular('children'));
    }

    public function testBasicPlural()
    {
        $this->assertEquals('children', Str::plural('child'));
    }

    public function testCaseSensitiveSingularUsage()
    {
        $this->assertEquals('Child', Str::singular('Children'));
        $this->assertEquals('CHILD', Str::singular('CHILDREN'));
        $this->assertEquals('Test', Str::singular('Tests'));
    }

    public function testCaseSensitiveSingularPlural()
    {
        $this->assertEquals('Children', Str::plural('Child'));
        $this->assertEquals('CHILDREN', Str::plural('CHILD'));
        $this->assertEquals('Tests', Str::plural('Test'));
    }

    public function testIfEndOfWordPlural()
    {
        $this->assertEquals('VortexFields', Str::plural('VortexField'));
        $this->assertEquals('MatrixFields', Str::plural('MatrixField'));
        $this->assertEquals('IndexFields', Str::plural('IndexField'));
        $this->assertEquals('VertexFields', Str::plural('VertexField'));
    }

    public function testSetLocaleSetsLocale()
    {
        Pluralizer::setLocale('foo');
        $this->assertEquals('foo', Pluralizer::getLocale());
    }

    public function testLocalizedPluralizerIsUsed()
    {
        Pluralizer::register('foo', FooPluralizer::class);
        Pluralizer::setLocale('foo');

        $this->assertEquals('bar', Pluralizer::plural('foo'));
        $this->assertEquals('baz', Pluralizer::singular('foo'));
    }

    public function testThrowsExceptionIfPluralizerInterfaceIsNotImplemented()
    {
        $this->expectException(UnexpectedValueException::class);

        Pluralizer::register('bar', BarPluralizer::class);
    }
}

class FooPluralizer implements PluralizerInterface
{
    public static function plural($value, $count)
    {
        return 'bar';
    }

    public static function singular($value)
    {
        return 'baz';
    }
}

class BarPluralizer
{
}
