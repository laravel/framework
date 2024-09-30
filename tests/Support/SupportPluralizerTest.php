<?php

namespace Illuminate\Tests\Support;

use Illuminate\Support\StrGrammar;
use PHPUnit\Framework\TestCase;

class SupportPluralizerTest extends TestCase
{
    public function testBasicSingular()
    {
        $this->assertSame('child', StrGrammar::singular('children'));
    }

    public function testBasicPlural()
    {
        $this->assertSame('children', StrGrammar::plural('child'));
        $this->assertSame('cod', StrGrammar::plural('cod'));
        $this->assertSame('The words', StrGrammar::plural('The word'));
        $this->assertSame('Bouquetés', StrGrammar::plural('Bouqueté'));
    }

    public function testCaseSensitiveSingularUsage()
    {
        $this->assertSame('Child', StrGrammar::singular('Children'));
        $this->assertSame('CHILD', StrGrammar::singular('CHILDREN'));
        $this->assertSame('Test', StrGrammar::singular('Tests'));
    }

    public function testCaseSensitiveSingularPlural()
    {
        $this->assertSame('Children', StrGrammar::plural('Child'));
        $this->assertSame('CHILDREN', StrGrammar::plural('CHILD'));
        $this->assertSame('Tests', StrGrammar::plural('Test'));
        $this->assertSame('children', StrGrammar::plural('cHiLd'));
    }

    public function testIfEndOfWordPlural()
    {
        $this->assertSame('VortexFields', StrGrammar::plural('VortexField'));
        $this->assertSame('MatrixFields', StrGrammar::plural('MatrixField'));
        $this->assertSame('IndexFields', StrGrammar::plural('IndexField'));
        $this->assertSame('VertexFields', StrGrammar::plural('VertexField'));

        // This is expected behavior, use "StrGrammar::pluralStudly" instead.
        $this->assertSame('RealHumen', StrGrammar::plural('RealHuman'));
    }

    public function testPluralWithNegativeCount()
    {
        $this->assertSame('test', StrGrammar::plural('test', 1));
        $this->assertSame('tests', StrGrammar::plural('test', 2));
        $this->assertSame('test', StrGrammar::plural('test', -1));
        $this->assertSame('tests', StrGrammar::plural('test', -2));
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
        $this->assertSame('Alien.', StrGrammar::plural('Alien.'));
        $this->assertSame('Alien!', StrGrammar::plural('Alien!'));
        $this->assertSame('Alien ', StrGrammar::plural('Alien '));
        $this->assertSame('50%', StrGrammar::plural('50%'));
    }

    public function testPluralAppliedForStringEndingWithNumericCharacter()
    {
        $this->assertSame('User1s', StrGrammar::plural('User1'));
        $this->assertSame('User2s', StrGrammar::plural('User2'));
        $this->assertSame('User3s', StrGrammar::plural('User3'));
    }

    public function testPluralSupportsArrays()
    {
        $this->assertSame('users', StrGrammar::plural('user', []));
        $this->assertSame('user', StrGrammar::plural('user', ['one']));
        $this->assertSame('users', StrGrammar::plural('user', ['one', 'two']));
    }

    public function testPluralSupportsCollections()
    {
        $this->assertSame('users', StrGrammar::plural('user', collect()));
        $this->assertSame('user', StrGrammar::plural('user', collect(['one'])));
        $this->assertSame('users', StrGrammar::plural('user', collect(['one', 'two'])));
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
        $this->assertSame($expected, StrGrammar::pluralStudly($value, $count));
    }
}
