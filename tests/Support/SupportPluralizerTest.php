<?php

namespace Illuminate\Tests\Support;

use Illuminate\Support\Str;
use PHPUnit\Framework\TestCase;

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

    public function testPlural()
    {
        $this->assertEquals('units', Str::plural('unit', -2));
        $this->assertEquals('units', Str::plural('unit', -1.001));
        $this->assertEquals('units', Str::plural('unit', -1 - 0.001 + 0.001));
        $this->assertEquals('units', Str::plural('unit', -1 + 0.001 - 0.001));
        $this->assertEquals('units', Str::plural('unit', -1));
        $this->assertEquals('units', Str::plural('unit', -0.9));
        $this->assertEquals('units', Str::plural('unit', -0.5));
        $this->assertEquals('units', Str::plural('unit', -0.1));
        $this->assertEquals('units', Str::plural('unit', 0));
        $this->assertEquals('units', Str::plural('unit', 0.1));
        $this->assertEquals('units', Str::plural('unit', 0.5));
        $this->assertEquals('units', Str::plural('unit', 0.9));
        $this->assertEquals('unit', Str::plural('unit', 1));
        $this->assertEquals('unit', Str::plural('unit', 1 + 0.001 - 0.001));
        $this->assertEquals('unit', Str::plural('unit', 1 - 0.001 + 0.001));
        $this->assertEquals('units', Str::plural('unit', 1.001));
        $this->assertEquals('units', Str::plural('unit', 2));
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
}
