<?php

use Mockery as m;
use Illuminate\Pagination\BootstrapPresenter;

class PaginationBootstrapPresenterTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testPresenterCanBeCreated()
	{
		$presenter = $this->getPresenter();
	}


	public function testSimpleRangeIsReturnedWhenCantBuildSlier()
	{
		$presenter = $this->getMock('Illuminate\Pagination\BootstrapPresenter', array('getPageRange', 'getPrevious', 'getNext'), array($paginator = $this->getPaginator()));
		$presenter->expects($this->once())->method('getPageRange')->with($this->equalTo(1), $this->equalTo(2))->will($this->returnValue('bar'));
		$presenter->expects($this->once())->method('getPrevious')->will($this->returnValue('foo'));
		$presenter->expects($this->once())->method('getNext')->will($this->returnValue('baz'));

		$this->assertEquals('foobarbaz', $presenter->render());
	}


	public function testGetPageRange()
	{
		$presenter = $this->getPresenter();
		$presenter->setCurrentPage(1);
		$content = $presenter->getPageRange(1, 2);

		$this->assertEquals('<li class="active"><a href="#">1</a></li><li><a href="http://foo.com?page=2">2</a></li>', $content);
	}


	public function testBeginningSliderIsCreatedWhenCloseToStart()
	{
		$presenter = $this->getMock('Illuminate\Pagination\BootstrapPresenter', array('getPageRange', 'getPrevious', 'getNext', 'getStart', 'getFinish'), array($paginator = $this->getPaginator()));
		$presenter->setLastPage(14);
		$presenter->expects($this->once())->method('getFinish')->will($this->returnValue('finish'));
		$presenter->expects($this->once())->method('getPrevious')->will($this->returnValue('previous'));
		$presenter->expects($this->once())->method('getNext')->will($this->returnValue('next'));
		$presenter->expects($this->once())->method('getPageRange')->with($this->equalTo(1), $this->equalTo(8))->will($this->returnValue('range'));

		$this->assertEquals('previousrangefinishnext', $presenter->render());
	}


	public function testEndingSliderIsCreatedWhenCloseToStart()
	{
		$presenter = $this->getMock('Illuminate\Pagination\BootstrapPresenter', array('getPageRange', 'getPrevious', 'getNext', 'getStart', 'getFinish'), array($paginator = $this->getPaginator()));
		$presenter->setLastPage(14);
		$presenter->setCurrentPage(13);
		$presenter->expects($this->once())->method('getStart')->will($this->returnValue('start'));
		$presenter->expects($this->once())->method('getPrevious')->will($this->returnValue('previous'));
		$presenter->expects($this->once())->method('getNext')->will($this->returnValue('next'));
		$presenter->expects($this->once())->method('getPageRange')->with($this->equalTo(6), $this->equalTo(14))->will($this->returnValue('range'));

		$this->assertEquals('previousstartrangenext', $presenter->render());
	}


	public function testSliderIsCreatedWhenCloseToStart()
	{
		$presenter = $this->getMock('Illuminate\Pagination\BootstrapPresenter', array('getPageRange', 'getPrevious', 'getNext', 'getStart', 'getFinish'), array($paginator = $this->getPaginator()));
		$presenter->setLastPage(30);
		$presenter->setCurrentPage(15);
		$presenter->expects($this->once())->method('getStart')->will($this->returnValue('start'));
		$presenter->expects($this->once())->method('getFinish')->will($this->returnValue('finish'));
		$presenter->expects($this->once())->method('getPrevious')->will($this->returnValue('previous'));
		$presenter->expects($this->once())->method('getNext')->will($this->returnValue('next'));
		$presenter->expects($this->once())->method('getPageRange')->with($this->equalTo(12), $this->equalTo(18))->will($this->returnValue('range'));

		$this->assertEquals('previousstartrangefinishnext', $presenter->render());
	}


	public function testPreviousLinkCanBeRendered()
	{
		$output = $this->getPresenter()->getPrevious();
		
		$this->assertEquals('<li class="disabled"><a href="#">&laquo;</a></li>', $output);

		$presenter = $this->getPresenter();
		$presenter->setCurrentPage(2);
		$output = $presenter->getPrevious();

		$this->assertEquals('<li><a href="http://foo.com?page=1">&laquo;</a></li>', $output);
	}


	public function testNextLinkCanBeRendered()
	{
		$presenter = $this->getPresenter();
		$presenter->setCurrentPage(2);
		$output = $presenter->getNext();

		$this->assertEquals('<li class="disabled"><a href="#">&raquo;</a></li>', $output);

		$presenter = $this->getPresenter();
		$presenter->setCurrentPage(1);
		$output = $presenter->getNext();

		$this->assertEquals('<li><a href="http://foo.com?page=2">&raquo;</a></li>', $output);
	}


	public function testGetStart()
	{
		$presenter = $this->getPresenter();
		$output = $presenter->getStart();

		$this->assertEquals('<li class="active"><a href="#">1</a></li><li><a href="http://foo.com?page=2">2</a></li><li class="disabled"><a href="#">...</a></li>', $output);
	}


	public function testGetFinish()
	{
		$presenter = $this->getPresenter();
		$output = $presenter->getFinish();

		$this->assertEquals('<li class="disabled"><a href="#">...</a></li><li class="active"><a href="#">1</a></li><li><a href="http://foo.com?page=2">2</a></li>', $output);
	}


	public function testGetAdjacentRange()
	{
		$presenter = $this->getMock('Illuminate\Pagination\BootstrapPresenter', array('getPageRange'), array($paginator = $this->getPaginator()));
		$presenter->expects($this->once())->method('getPageRange')->with($this->equalTo(1), $this->equalTo(7))->will($this->returnValue('foo'));
		$presenter->setCurrentPage(4);

		$this->assertEquals('foo', $presenter->getAdjacentRange());
	}



	protected function getPresenter()
	{
		return new BootstrapPresenter($this->getPaginator());
	}


	protected function getPaginator()
	{
		$paginator = m::mock('Illuminate\Pagination\Paginator');
		$paginator->shouldReceive('getLastPage')->once()->andReturn(2);
		$paginator->shouldReceive('getCurrentPage')->once()->andReturn(1);
		$paginator->shouldReceive('getUrl')->andReturnUsing(function($page) { return 'http://foo.com?page='.$page; });
		return $paginator;
	}

}