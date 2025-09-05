<?php

namespace Illuminate\Tests\Support;

use Illuminate\Support\Str;
use Illuminate\Support\Pluralizer;
use PHPUnit\Framework\TestCase;

class SupportPluralizerTest extends TestCase
{
    /**
     * @dataProvider uncountableWordsProvider
     */
    public function testUncountableWordsAreNotPluralized(string $word)
    {
        $this->assertEquals($word, Pluralizer::plural($word));
    }

    /**
     * @dataProvider uppercaseWordsProvider
     */
    public function testPluralizationOfUppercaseAcronym(string $word, string $expected)
    {
        $this->assertEquals($expected, Pluralizer::plural($word, 2, true));
    }

    /**
     * @dataProvider normalWordsProvider
     */
    public function testNormalWordsArePluralized(string $word, string $expected)
    {
        $this->assertEquals($expected, Pluralizer::plural($word, 2, false));
    }

    /**
     * Data provider for uncountable words.
     *
     * @return array
     */
    public function uncountableWordsProvider(): array
    {
        return [
            ['recommended'],
            ['related'],
        ];
    }

    /**
     * Data provider for uppercase words.
     *
     * @return array
     */
    public function uppercaseWordsProvider(): array
    {
        return [
            ['CD', 'CDs'],
            ['DVD', 'DVDs'],
        ];
    }

    /**
     * Data provider for normal words.
     *
     * @return array
     */
    public function normalWordsProvider(): array
    {
        return [
            ['child', 'children'],
            ['person', 'people'],
            ['tooth', 'teeth'],
        ];
    }

    public function testBasicSingular()
    {
        $this->assertSame('child', Pluralizer::singular('children'));
    }

    public function testBasicPlural()
    {
        $this->assertSame('children', Pluralizer::plural('child'));
        $this->assertSame('cod', Pluralizer::plural('cod'));
        $this->assertSame('The words', Pluralizer::plural('The word'));
        $this->assertSame('Bouquetés', Pluralizer::plural('Bouqueté'));
    }

    public function testCaseSensitiveSingularUsage()
    {
        $this->assertSame('Child', Pluralizer::singular('Children'));
        $this->assertSame('CHILD', Pluralizer::singular('CHILDREN'));
        $this->assertSame('Test', Pluralizer::singular('Tests'));
    }

    public function testCaseSensitiveSingularPlural()
    {
        $this->assertSame('Children', Pluralizer::plural('Child'));
        $this->assertSame('CHILDREN', Pluralizer::plural('CHILD'));
        $this->assertSame('Tests', Pluralizer::plural('Test'));
        $this->assertSame('children', Pluralizer::plural('cHiLd'));
    }
}