<?php

namespace Illuminate\Tests\Support;

use Illuminate\Support\Str;
use PHPUnit\Framework\TestCase;

class SupportPluralizerTest extends TestCase
{
    public function testBasicSingular()
    {
        $this->assertSame('child', Str::singular('children'));
    }

    public function testBasicPlural()
    {
        $this->assertSame('children', Str::plural('child'));
        $this->assertSame('cod', Str::plural('cod'));
        $this->assertSame('The words', Str::plural('The word'));
        $this->assertSame('Bouquetés', Str::plural('Bouqueté'));
    }

    public function testCaseSensitiveSingularUsage()
    {
        $this->assertSame('Child', Str::singular('Children'));
        $this->assertSame('CHILD', Str::singular('CHILDREN'));
        $this->assertSame('Test', Str::singular('Tests'));
    }

    public function testCaseSensitiveSingularPlural()
    {
        $this->assertSame('Children', Str::plural('Child'));
        $this->assertSame('CHILDREN', Str::plural('CHILD'));
        $this->assertSame('Tests', Str::plural('Test'));
        $this->assertSame('children', Str::plural('cHiLd'));
    }

    public function testIfEndOfWordPlural()
    {
        $this->assertSame('VortexFields', Str::plural('VortexField'));
        $this->assertSame('MatrixFields', Str::plural('MatrixField'));
        $this->assertSame('IndexFields', Str::plural('IndexField'));
        $this->assertSame('VertexFields', Str::plural('VertexField'));

        // This is expected behavior, use "Str::pluralStudly" instead.
        $this->assertSame('RealHumen', Str::plural('RealHuman'));
    }

    public function testPluralWithNegativeCount()
    {
        $this->assertSame('test', Str::plural('test', 1));
        $this->assertSame('tests', Str::plural('test', 2));
        $this->assertSame('test', Str::plural('test', -1));
        $this->assertSame('tests', Str::plural('test', -2));
    }

    public function testPluralStudly()
    {
        $this->assertPluralStudly('RealHumans', 'RealHuman');
        $this->assertPluralStudly('Models', 'Model');
        $this->assertPluralStudly('VortexFields', 'VortexField');
        $this->assertPluralStudly('MultipleWordsInOneStrings', 'MultipleWordsInOneString');
    }

    public function testPluralStudlyWithCount()
    {
        $this->assertPluralStudly('RealHuman', 'RealHuman', 1);
        $this->assertPluralStudly('RealHumans', 'RealHuman', 2);
        $this->assertPluralStudly('RealHuman', 'RealHuman', -1);
        $this->assertPluralStudly('RealHumans', 'RealHuman', -2);
    }

    public function testPluralNotAppliedForStringEndingWithNonAlphanumericCharacter()
    {
        $this->assertSame('Alien.', Str::plural('Alien.'));
        $this->assertSame('Alien!', Str::plural('Alien!'));
        $this->assertSame('Alien ', Str::plural('Alien '));
        $this->assertSame('50%', Str::plural('50%'));
    }

    public function testPluralAppliedForStringEndingWithNumericCharacter()
    {
        $this->assertSame('User1s', Str::plural('User1'));
        $this->assertSame('User2s', Str::plural('User2'));
        $this->assertSame('User3s', Str::plural('User3'));
    }

    public function testPluralSupportsArrays()
    {
        $this->assertSame('users', Str::plural('user', []));
        $this->assertSame('user', Str::plural('user', ['one']));
        $this->assertSame('users', Str::plural('user', ['one', 'two']));
    }

    public function testPluralSupportsCollections()
    {
        $this->assertSame('users', Str::plural('user', collect()));
        $this->assertSame('user', Str::plural('user', collect(['one'])));
        $this->assertSame('users', Str::plural('user', collect(['one', 'two'])));
    }

    public function testPluralStudlySupportsArrays()
    {
        $this->assertPluralStudly('SomeUsers', 'SomeUser', []);
        $this->assertPluralStudly('SomeUser', 'SomeUser', ['one']);
        $this->assertPluralStudly('SomeUsers', 'SomeUser', ['one', 'two']);
    }

    public function testPluralStudlySupportsCollections()
    {
        $this->assertPluralStudly('SomeUsers', 'SomeUser', collect());
        $this->assertPluralStudly('SomeUser', 'SomeUser', collect(['one']));
        $this->assertPluralStudly('SomeUsers', 'SomeUser', collect(['one', 'two']));
    }

    private function assertPluralStudly($expected, $value, $count = 2)
    {
        $this->assertSame($expected, Str::pluralStudly($value, $count));
    }
}
