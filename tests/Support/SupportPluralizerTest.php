<?php

class SupportPluralizerTest extends PHPUnit_Framework_TestCase {


    public function testBasicSingular()
    {
        $this->assertEquals('child', str_singular('children'));
    }


    public function testBasicPlural()
    {
        $this->assertEquals('children', str_plural('child'));
    }


    public function testCaseSensitiveSingularUsage()
    {
        $this->assertEquals('Child', str_singular('Children'));
        $this->assertEquals('CHILD', str_singular('CHILDREN'));
        $this->assertEquals('Test', str_singular('Tests'));
    }


    public function testCaseSensitiveSingularPlural()
    {
        $this->assertEquals('Children', str_plural('Child'));
        $this->assertEquals('CHILDREN', str_plural('CHILD'));
        $this->assertEquals('Tests', str_plural('Test'));
    }


    public function testIfEndOfWordPlural()
    {
        $this->assertEquals('VortexFields', str_plural('VortexField'));
        $this->assertEquals('MatrixFields', str_plural('MatrixField'));
        $this->assertEquals('IndexFields', str_plural('IndexField'));
        $this->assertEquals('VertexFields', str_plural('VertexField'));
    }

}
