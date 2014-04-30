<?php

use Mockery as m;
use Illuminate\Pagination\Paginator;

class PaginationPaginatorTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testPaginationContextIsSetupCorrectly()
	{
		$p = new Paginator($env = m::mock('Illuminate\Pagination\Environment'), array('foo', 'bar', 'baz'), 3, 2);
		$env->shouldReceive('getCurrentPage')->once()->andReturn(1);
		$p->setupPaginationContext();

		$this->assertEquals(2, $p->getLastPage());
		$this->assertEquals(1, $p->getCurrentPage());
	}


	public function testPaginationContextSetsUpRangeCorrectly()
	{
		$p = new Paginator($env = m::mock('Illuminate\Pagination\Environment'), array('foo', 'bar', 'baz'), 3, 2);
		$env->shouldReceive('getCurrentPage')->once()->andReturn(1);
		$p->setupPaginationContext();

		$this->assertEquals(1, $p->getFrom());
		$this->assertEquals(2, $p->getTo());
	}


	public function testPaginationContextHandlesHugeCurrentPage()
	{
		$p = new Paginator($env = m::mock('Illuminate\Pagination\Environment'), array('foo', 'bar', 'baz'), 3, 2);
		$env->shouldReceive('getCurrentPage')->once()->andReturn(15);
		$p->setupPaginationContext();

		$this->assertEquals(2, $p->getLastPage());
		$this->assertEquals(2, $p->getCurrentPage());
	}


	public function testPaginationContextHandlesPageLessThanOne()
	{
		$p = new Paginator($env = m::mock('Illuminate\Pagination\Environment'), array('foo', 'bar', 'baz'), 3, 2);
		$env->shouldReceive('getCurrentPage')->once()->andReturn(-1);
		$p->setupPaginationContext();

		$this->assertEquals(2, $p->getLastPage());
		$this->assertEquals(1, $p->getCurrentPage());
	}


	public function testPaginationContextHandlesPageLessThanOneAsString()
	{
		$p = new Paginator($env = m::mock('Illuminate\Pagination\Environment'), array('foo', 'bar', 'baz'), 3, 2);
		$env->shouldReceive('getCurrentPage')->once()->andReturn('-1');
		$p->setupPaginationContext();

		$this->assertEquals(2, $p->getLastPage());
		$this->assertEquals(1, $p->getCurrentPage());
	}


	public function testPaginationContextHandlesPageInvalidFormat()
	{
		$p = new Paginator($env = m::mock('Illuminate\Pagination\Environment'), array('foo', 'bar', 'baz'), 3, 2);
		$env->shouldReceive('getCurrentPage')->once()->andReturn('abc');
		$p->setupPaginationContext();

		$this->assertEquals(2, $p->getLastPage());
		$this->assertEquals(1, $p->getCurrentPage());
	}


	public function testPaginationContextHandlesPageMissing()
	{
		$p = new Paginator($env = m::mock('Illuminate\Pagination\Environment'), array('foo', 'bar', 'baz'), 3, 2);
		$env->shouldReceive('getCurrentPage')->once()->andReturn(null);
		$p->setupPaginationContext();

		$this->assertEquals(2, $p->getLastPage());
		$this->assertEquals(1, $p->getCurrentPage());
	}


	public function testGetLinksCallsEnvironmentProperly()
	{
		$p = new Paginator($env = m::mock('Illuminate\Pagination\Environment'), array('foo', 'bar', 'baz'), 3, 2);
		$env->shouldReceive('getPaginationView')->once()->with($p, null)->andReturn('foo');

		$this->assertEquals('foo', $p->links());
	}


	public function testGetUrlProperlyFormatsUrl()
	{
		$p = new Paginator($env = m::mock('Illuminate\Pagination\Environment'), array('foo', 'bar', 'baz'), 3, 2);
		$env->shouldReceive('getCurrentUrl')->twice()->andReturn('http://foo.com');
		$env->shouldReceive('getPageName')->twice()->andReturn('page');

		$this->assertEquals('http://foo.com?page=1', $p->getUrl(1));
		$p->addQuery('foo', 'bar');
		$this->assertEquals('http://foo.com?foo=bar&page=1', $p->getUrl(1));
	}


	public function testEnvironmentAccess()
	{
		$p = new Paginator($env = m::mock('Illuminate\Pagination\Environment'), array('foo', 'bar', 'baz'), 3, 2);
		$this->assertInstanceOf('Illuminate\Pagination\Environment', $p->getEnvironment());
	}


	public function testPaginatorIsCountable()
	{
		$p = new Paginator($env = m::mock('Illuminate\Pagination\Environment'), array('foo', 'bar', 'baz'), 3, 2);

		$this->assertEquals(3, count($p));
	}


	public function testPaginatorIsIterable()
	{
		$p = new Paginator($env = m::mock('Illuminate\Pagination\Environment'), array('foo', 'bar', 'baz'), 3, 2);

		$this->assertInstanceOf('ArrayIterator', $p->getIterator());
		$this->assertEquals(array('foo', 'bar', 'baz'), $p->getIterator()->getArrayCopy());
	}


	public function testGetUrlAddsFragment()
	{
		$p = new Paginator($env = m::mock('Illuminate\Pagination\Environment'), array('foo', 'bar', 'baz'), 3, 2);
		$env->shouldReceive('getCurrentUrl')->twice()->andReturn('http://foo.com');
		$env->shouldReceive('getPageName')->twice()->andReturn('page');

		$p->fragment("a-fragment");

		$this->assertEquals('http://foo.com?page=1#a-fragment', $p->getUrl(1));
		$p->addQuery('foo', 'bar');
		$this->assertEquals('http://foo.com?foo=bar&page=1#a-fragment', $p->getUrl(1));
	}


	public function testGetUrlHasPriorityOverAppends()
	{
		$p = new Paginator($env = m::mock('Illuminate\Pagination\Environment'), array('foo', 'bar', 'baz'), 3, 2);
		$env->shouldReceive('getCurrentUrl')->twice()->andReturn('http://foo.com');
		$env->shouldReceive('getPageName')->twice()->andReturn('page');

		$this->assertEquals('http://foo.com?page=1', $p->getUrl(1));
		$p->addQuery('page', '2');
		$this->assertEquals('http://foo.com?page=1', $p->getUrl(1));
	}


	public function testPaginatorDecoratesCollection()
	{
		$p = new Paginator(m::mock('Illuminate\Pagination\Environment'), array('a', 'b', 'c'), 3, 2);
		$last = $p->last();

		$this->assertEquals('c', $last);
	}

}
