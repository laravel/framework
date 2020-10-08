<?php

namespace Illuminate\Tests\Support;

use Illuminate\Pagination\LengthAwarePaginator;
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

    private function assertPluralStudly($expected, $value, $count = 2)
    {
        $this->assertSame($expected, Str::pluralStudly($value, $count));
    }

    public function testPluralWithArray()
    {
        $this->assertSame('tests', Str::plural('test', []));
        $this->assertSame('test', Str::plural('test', ['item1']));
        $this->assertSame('tests', Str::plural('test', ['item1', 'item2']));
    }

    public function testPluralWithCountables()
    {
        $this->assertSame('tests', Str::plural('test', collect([])));
        $this->assertSame('test', Str::plural('test', collect(['item1'])));
        $this->assertSame('tests', Str::plural('test', collect(['item1', 'item2'])));

        $paginator = new LengthAwarePaginator(['item1', 'item2', 'item3'], 30, 10);
        $this->assertSame('tests', Str::plural('test', $paginator));
    }

    public function testPluralStudyWithArray()
    {
        $this->assertPluralStudly('RealHumans', 'RealHuman', []);
        $this->assertPluralStudly('RealHuman', 'RealHuman', ['item1']);
        $this->assertPluralStudly('RealHumans', 'RealHuman', ['item1', 'item2']);
    }

    public function testPluralStudyWithCountables()
    {
        $this->assertPluralStudly('RealHumans', 'RealHuman', collect([]));
        $this->assertPluralStudly('RealHuman', 'RealHuman', collect(['item1']));
        $this->assertPluralStudly('RealHumans', 'RealHuman', collect(['item1', 'item2']));

        $paginator = new LengthAwarePaginator(['item1', 'item2', 'item3'], 30, 10);
        $this->assertPluralStudly('RealHumans', 'RealHuman', $paginator);
    }
}
