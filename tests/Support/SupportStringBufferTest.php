<?php

use Illuminate\Support\StringBuffer;

class SupportStringBufferTest extends PHPUnit_Framework_TestCase
{
    public function testCanBeInstantiated()
    {
        $string = new StringBuffer('string');
        $this->assertEquals('string', $string->get());

        $string = new StringBuffer(new TestToStringObject);
        $this->assertEquals('string', $string->get());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testCannotBeInstantiatedFromArray()
    {
        new StringBuffer([1,2,3]);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testCannotBeInstantiatedFromObject()
    {
        new StringBuffer(new TestObjectWithoutToString);
    }

    public function testCanBeCastedToString()
    {
        $string = new StringBuffer('string');
        $this->assertEquals('string', $string->get());
        $this->assertEquals('string', (string) $string);
    }

    public function testLowerCaseFirst()
    {
        $string = new StringBuffer('Hello World!');
        $this->assertEquals('hello World!', $string->lcfirst()->get());

        $string = new StringBuffer('Τάχιστη');
        $this->assertEquals('τάχιστη', $string->lcfirst()->get());
    }

    public function testExplodeByDelimiters()
    {
        $string = new StringBuffer('Hello/World!');
        $this->assertEquals(['Hello', 'World!'], $string->explode('/')->all());

        $string = new StringBuffer('Laravel Php--Framework');
        $this->assertEquals(['Laravel', 'Php', 'Framework'], $string->explode([' ', '--'])->all());

        $string = new StringBuffer('Τάχιστη');
        $this->assertEquals(['Τά', 'ιστη'], $string->explode('χ')->all());
    }

    public function testIndexOf()
    {
        $string = new StringBuffer('Hello World!!!');
        $this->assertEquals(11, $string->indexOf('!'));

        $string = new StringBuffer('Pešci, sčistite cestišče!');
        $this->assertEquals(21, $string->indexOf('šč'));
    }

    public function testLastIndexOf()
    {
        $string = new StringBuffer('Hello World!!!!');
        $this->assertEquals(14, $string->lastIndexOf('!'));

        $string = new StringBuffer('Pešci, sčistite cestišče!');
        $this->assertEquals(22, $string->lastIndexOf('č'));
    }

    public function testReplaceWithSubstring()
    {
        $string = new StringBuffer('Hello World!');
        $this->assertEquals('Hello Laravel!', $string->replace('World', 'Laravel')->get());

        $string = new StringBuffer('Pešci, sčistite cestišče!');
        $this->assertEquals('Pesci, scistite cestisce!', $string->replace(['č','š'], ['c','s'], $count1)->get());
        $this->assertEquals(4, $count1);

        $string = new StringBuffer('Hello, World.');
        $this->assertEquals('Hello! World!', $string->replace([',','.'], ['!'], $count2)->get());
        $this->assertEquals('Hello! World!', $string->replace([',','.'], '!', $count3)->get());
        $this->assertEquals(2, $count2);
        $this->assertEquals(2, $count3);
    }

    public function testSplitByWords()
    {
        $string = new StringBuffer('one, two ,   three');
        $this->assertEquals(['one', 'two', 'three'], $string->words()->all());

        $string = new StringBuffer("\nHello? \n Laravel: The Php Framework... ");
        $this->assertEquals(['Hello', 'Laravel', 'The', 'Php', 'Framework'], $string->words()->all());
    }

    public function testSplitByLines()
    {
        $string = new StringBuffer("one\ntwo\r\nthree\rfour\n\nfive");
        $this->assertEquals(['one', 'two', 'three', 'four', '', 'five'], $string->lines()->all());
    }

    public function testAppendAndPrepend()
    {
        $string = new StringBuffer('Hello');
        $this->assertEquals('Hello world!', $string->append(' ')->append('world')->append('!')->get());

        $string = new StringBuffer('world');
        $this->assertEquals('Hello world!', $string->prepend(' ')->prepend('Hello')->append('!')->get());
    }

    public function testTrimming()
    {
        $string = new StringBuffer(' Hello world! ');
        $this->assertEquals('Hello world!', $string->trim()->get());

        $string = new StringBuffer(' Hello world! ');
        $this->assertEquals('Hello world! ', $string->ltrim()->get());

        $string = new StringBuffer(' Hello world! ');
        $this->assertEquals(' Hello world!', $string->rtrim()->get());

        $string = new StringBuffer('"Hello world!"');
        $this->assertEquals('Hello world', $string->trim('"!')->get());

        $string = new StringBuffer('Ψ Hello world! Ψ');
        $this->assertEquals('Hello world', $string->trim('Ψ! ')->get());

        $string = new StringBuffer('Ψ Hello world! Ψ');
        $this->assertEquals('Hello world! Ψ', $string->ltrim('Ψ! ')->get());

        $string = new StringBuffer('Ψ Hello world! Ψ');
        $this->assertEquals('Ψ Hello world', $string->rtrim('Ψ! ')->get());
    }

    public function testWordAtIndex()
    {
        $string = new StringBuffer('Hello, world!');
        $this->assertEquals('world', $string->wordAt(1)->get());
    }

    public function testOffsets()
    {
        $string = new StringBuffer('Τάχιστη');
        $this->assertEquals('ά', $string[1]);
        $this->assertTrue(isset($string[6]));
        $this->assertFalse(isset($string[7]));

        $string[1] = 'a';
        $this->assertEquals('Τaχιστη', $string->get());

        $string = new StringBuffer('Τάχιστη');
        unset($string[1]);
        $this->assertEquals('Τχιστη', $string->get());
    }

    public function testMethodsDeferredToStrHelper()
    {
        $this->assertEquals('Hello world', (new StringBuffer('hello world'))->ucfirst()->get());
        $this->assertTrue((new StringBuffer('hello world'))->startsWith('hello'));
        $this->assertTrue((new StringBuffer('hello world'))->endsWith('world'));
        $this->assertTrue((new StringBuffer('hello world'))->contains('world'));
        $this->assertTrue((new StringBuffer('hello world'))->equals('hello world'));
        $this->assertTrue((new StringBuffer('hello/world'))->matches('*/*'));
        $this->assertEquals('Hell', (new StringBuffer('Hello world'))->substring(0,4)->get());
        $this->assertEquals('CcZzSs', (new StringBuffer('ČčŽžŠš'))->toAscii()->get());
        $this->assertEquals('helloWorld', (new StringBuffer('hello world'))->toCamel()->get());
        $this->assertEquals('hello world', (new StringBuffer('HELLO WORLD'))->toLower()->get());
        $this->assertEquals('hello_world', (new StringBuffer('helloWorld'))->toSnake()->get());
        $this->assertEquals('HelloWorld', (new StringBuffer('hello world'))->toStudly()->get());
        $this->assertEquals('Hello World', (new StringBuffer('hello world'))->toTitle()->get());
        $this->assertEquals('HELLO WORLD', (new StringBuffer('hello world'))->toUpper()->get());
        $this->assertEquals('worlds', (new StringBuffer('world'))->toPlural()->get());
        $this->assertEquals('world', (new StringBuffer('worlds'))->toSingular()->get());
        $this->assertEquals('hello-world', (new StringBuffer('hello world'))->toSlug()->get());
        $this->assertEquals(11, (new StringBuffer('hello world'))->length());
        $this->assertEquals('hello...', (new StringBuffer('hello world'))->limit(5)->get());
        $this->assertEquals('hello...', (new StringBuffer('hello world'))->limitWords(1)->get());
    }
}

class TestToStringObject
{
    public function __toString()
    {
        return 'string';
    }
}

class TestObjectWithoutToString {}