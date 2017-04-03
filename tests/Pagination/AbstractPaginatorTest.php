<?php

namespace Illuminate\Tests\Pagination;

use PHPUnit\Framework\TestCase;
use Illuminate\Pagination\Paginator;
use Illuminate\Pagination\AbstractPaginator;

class AbstractPaginatorTest extends TestCase
{
    public function setUp()
    {
        $items = ['item1', 'item2', 'item3', 'item4', 'item5'];
        $perPage = 2;
        $currentPage = 3;
        $this->concrete = new Paginator($items, $perPage, $currentPage);
    }

    public function tearDown()
    {
        unset($this->concrete);
    }

    public function testPreviousPageUrl()
    {
        $setCurrentPage = $this->methodSetPublic('setCurrentPage');

        $this->assertEquals('/?page=2', $this->concrete->previousPageUrl());

        $setCurrentPage->invoke($this->concrete, 1);
        $this->assertEquals('/?page=2', $this->concrete->previousPageUrl());
    }

    public function testNextPageUrl()
    {
        $setCurrentPage = $this->methodSetPublic('setCurrentPage');

        $this->assertEquals('/?page=4', $this->concrete->nextPageUrl());

        $setCurrentPage->invoke($this->concrete, 1);
        $this->assertEquals('/?page=4', $this->concrete->nextPageUrl());
    }

    public function testGetUrlRange()
    {
    	$pagesRange = [1 => '/?page=1', 2 => '/?page=2', 3 => '/?page=3'];

    	$this->assertEquals($pagesRange,  $this->concrete->getUrlRange(1,3));
    	$this->assertNotEquals(array_reverse($pagesRange),  $this->concrete->getUrlRange(1,3));
    }

    public function testSetPageName()
    {
    	$this->concrete->setPageName('differentPageName');

    	$this->assertEquals('differentPageName', $this->concrete->getPageName());
    }

    public function testGetPageName()
    {
    	$this->concrete->setPageName('differentPageName');

    	$this->assertEquals('differentPageName', $this->concrete->getPageName());
    }

    public function testUrl()
    {
    	$this->assertEquals('/?page=1', $this->concrete->url(0));
    	$this->assertEquals('/?page=1', $this->concrete->url(1));
    	$this->assertEquals('/?page=2', $this->concrete->url(2));
    }

    public function testBuildFragment()
    {
        $buildFragment = $this->methodSetPublic('buildFragment');
        $fragment = $this->propertySetPublic('fragment');

        $results1 = $buildFragment->invoke($this->concrete);

        $fragment->setValue($this->concrete, 'foo');
        $results2 = $buildFragment->invoke($this->concrete);

        $this->assertEquals('', $results1);
        $this->assertEquals('#foo', $results2);
    }

    public function testFragment()
    {
        $fragment = $this->propertySetPublic('fragment');

        $this->concrete->fragment();
        $results1 = $this->getPropertyValue($fragment);

        $this->concrete->fragment('foo');
        $results2 = $this->getPropertyValue($fragment);

        $instance = $this->concrete->fragment('bar');

        $this->assertEquals(null, $results1);
        $this->assertEquals('foo', $results2);
        $this->assertInstanceOf(Paginator::class, $instance);
    }

    public function testAppends()
    {
        $query = $this->propertySetPublic('query');
        $pageName = $this->propertySetPublic('pageName');

        $this->flushArray($query);
        $results1 = $this->getPropertyValue($query);

        $this->flushArray($query);
        $this->concrete->appends('foo');
        $results2 = $this->getPropertyValue($query);

        $this->flushArray($query);
        $this->concrete->appends('foo', 'bar');
        $results3 = $this->getPropertyValue($query);

        $this->flushArray($query);
        $this->concrete->appends('foo', 'bar');
        $this->concrete->appends('foo', 'baz');
        $results4 = $this->getPropertyValue($query);

        $this->flushArray($query);
        $this->concrete->appends('foo', 'bar');
        $this->concrete->appends('baz', 'qux');
        $results5 = $this->getPropertyValue($query);

        $this->flushArray($query);
        $this->concrete->appends(['foo' => 'bar', 'baz' => 'qux']);
        $results6 = $this->getPropertyValue($query);

        $this->flushArray($query);
        $this->concrete->appends('page', 'bar'); // this should not be appended
        $this->concrete->appends('foo', 'baz');
        $results7 = $this->getPropertyValue($query);

        $this->flushArray($query);
        $pageName = $this->setPropertyValue($pageName, 'otherPage');
        $this->concrete->appends('otherPage', 'bar'); // this should not be appended
        $this->concrete->appends('foo', 'baz');
        $results8 = $this->getPropertyValue($query);

        $instance = $this->concrete->appends('bar');

        $this->assertEquals([], $results1);
        $this->assertEquals(['foo' => null], $results2);
        $this->assertEquals(['foo' => 'bar'], $results3);
        $this->assertEquals(['foo' => 'baz'], $results4);
        $this->assertEquals(['foo' => 'bar', 'baz' => 'qux'], $results5);
        $this->assertEquals(['foo' => 'bar', 'baz' => 'qux'], $results6);
        $this->assertNotEquals(['page' => 'bar', 'foo' => 'baz'], $results7);
        $this->assertNotEquals(['otherPage' => 'bar', 'foo' => 'baz'], $results8);
        $this->assertInstanceOf(Paginator::class, $instance);
    }

    public function testAppendArray()
    {
        $appendArray = $this->methodSetPublic('appendArray');
        $query = $this->propertySetPublic('query');

        $this->flushArray($query);
        $appendArray->invoke($this->concrete, ['foo' => 'bar']);
        $results1 = $this->getPropertyValue($query);

        $this->flushArray($query);
        $appendArray->invoke($this->concrete, ['page' => 'bar']);
        $results2 = $this->getPropertyValue($query);

        $instance = $appendArray->invoke($this->concrete, []);

        $this->assertEquals(['foo' => 'bar'], $results1);
        $this->assertNotEquals(['page' => 'bar'], $results2);
        $this->assertInstanceOf(Paginator::class, $instance);
    }

    public function testAddQuery()
    {
        $addQuery = $this->methodSetPublic('addQuery');
        $query = $this->propertySetPublic('query');

        $this->flushArray($query);
        $addQuery->invoke($this->concrete, 'foo', 'bar');
        $results1 = $this->getPropertyValue($query);

        $this->flushArray($query);
        $addQuery->invoke($this->concrete, 'page', 'bar');
        $results2 = $this->getPropertyValue($query);

        $instance = $addQuery->invoke($this->concrete, 'foo', 'bar');

        $this->assertEquals(['foo' => 'bar'], $results1);
        $this->assertNotEquals(['page' => 'bar'], $results2);
        $this->assertInstanceOf(Paginator::class, $instance);
    }

    public function testItems()
    {
        // the number of items is set to perPage.
        $this->assertCount(2, $this->concrete->items());
    }

    protected function propertySetPublic($property)
    {
        $property = new \ReflectionProperty(AbstractPaginator::class, $property);
        $property->setAccessible(true);

        return $property;
    }

    protected function methodSetPublic($method)
    {
        $method = new \ReflectionMethod($this->concrete, $method);
        $method->setAccessible(true);

        return $method;
    }

    protected function flushArray(\ReflectionProperty $property)
    {
        $property->setValue($this->concrete, []);
    }

    protected function getPropertyValue(\ReflectionProperty $property)
    {
        return $property->getValue($this->concrete);
    }

    protected function setPropertyValue(\ReflectionProperty $property, $value)
    {
        return $property->setValue($this->concrete, $value);
    }
}
