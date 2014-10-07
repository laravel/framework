<?php

use Illuminate\Pagination\UrlWindow;
use Illuminate\Pagination\Paginator as Paginator;
use Illuminate\Pagination\BootstrapThreePresenter as BootstrapPresenter;

class PaginationPaginatorTest extends PHPUnit_Framework_TestCase {

	public function testPaginatorCanGiveMeRelevantPageInformation()
	{
		$p = new Paginator($array = ['item1', 'item2', 'item3', 'item4'], 4, 2, 2);

		$this->assertEquals(2, $p->lastPage());
		$this->assertEquals(2, $p->currentPage());
		$this->assertEquals(['item3', 'item4'], $p->items());
	}


	public function testPaginatorCanGenerateUrls()
	{
		$p = new Paginator($array = ['item1', 'item2', 'item3', 'item4'], 4, 2, 2, ['path' => 'http://website.com', 'pageName' => 'foo']);

		$this->assertEquals(['http://website.com/?foo=1', 'http://website.com/?foo=2'], $p->urls());
		$this->assertEquals('http://website.com/?foo=2', $p->url($p->currentPage()));
		$this->assertEquals('http://website.com/?foo=1', $p->url($p->currentPage() - 1));
		$this->assertNull($p->url($p->currentPage() + 1));
		$this->assertNull($p->url($p->currentPage() - 2));
	}


	public function testPresenterCanDetermineIfThereAreAnyPagesToShow()
	{
		$p = new Paginator($array = ['item1', 'item2', 'item3', 'item4'], 4, 2, 2);
		$window = new UrlWindow($p);
		$this->assertTrue($window->hasPages());
	}


	public function testPresenterCanGetAUrlRangeForASmallNumberOfUrls()
	{
		$p = new Paginator($array = ['item1', 'item2', 'item3', 'item4'], 4, 2, 2);
		$window = new UrlWindow($p);
		$this->assertEquals(['first' => [1 => '/?page=1', 2 => '/?page=2'], 'slider' => null, 'last' => null], $window->get());
	}


	public function testPresenterCanGetAUrlRangeForAWindowOfLinks()
	{
		$array = [];
		for ($i = 1; $i <= 13; $i++)
			$array[$i] = 'item'.$i;
		$p = new Paginator($array, count($array), 7, 1);
		$window = new UrlWindow($p);
		$slider = [];
		for ($i = 4; $i <= 10; $i++)
			$slider[$i] = '/?page='.$i;

		$this->assertEquals(['first' => [1 => '/?page=1', 2 => '/?page=2'], 'slider' => $slider, 'last' => [12 => '/?page=12', 13 => '/?page=13']], $window->get());

		/**
		 * Test Being Near The End Of The List
		 */
		$p = new Paginator($array, count($array), 8, 1);
		$window = new UrlWindow($p);
		$last = [];
		for ($i = 5; $i <= 13; $i++)
			$last[$i] = '/?page='.$i;

		$this->assertEquals(['first' => [1 => '/?page=1', 2 => '/?page=2'], 'slider' => null, 'last' => $last], $window->get());
	}


	public function testBootstrapPresenterCanGeneratorLinksForSlider()
	{
		return;
		$array = [];
		for ($i = 1; $i <= 13; $i++)
			$array[$i] = 'item'.$i;
		$p = new Paginator($array, count($array), 7, 1);
		$presenter = new BootstrapPresenter($p);

		$this->assertEquals(trim(file_get_contents(__DIR__.'/fixtures/slider.html')), $presenter->render());
	}


	public function testBootstrapPresenterCanGeneratorLinksForTooCloseToBeginning()
	{
		return;
		$array = [];
		for ($i = 1; $i <= 13; $i++)
			$array[$i] = 'item'.$i;
		$p = new Paginator($array, count($array), 2, 1);
		$presenter = new BootstrapPresenter($p);

		$this->assertEquals(trim(file_get_contents(__DIR__.'/fixtures/beginning.html')), $presenter->render());
	}


	public function testBootstrapPresenterCanGeneratorLinksForTooCloseToEnding()
	{
		$array = [];
		for ($i = 1; $i <= 13; $i++)
			$array[$i] = 'item'.$i;
		$p = new Paginator($array, count($array), 12, 1);
		$presenter = new BootstrapPresenter($p);

		$this->assertEquals(trim(file_get_contents(__DIR__.'/fixtures/ending.html')), $presenter->render());
	}


	public function testBootstrapPresenterCanGeneratorLinksForWhenOnLastPage()
	{
		$array = [];
		for ($i = 1; $i <= 13; $i++)
			$array[$i] = 'item'.$i;
		$p = new Paginator($array, count($array), 13, 1);
		$presenter = new BootstrapPresenter($p);

		$this->assertEquals(trim(file_get_contents(__DIR__.'/fixtures/last_page.html')), $presenter->render());
	}


	public function testBootstrapPresenterCanGeneratorLinksForWhenOnFirstPage()
	{
		$array = [];
		for ($i = 1; $i <= 13; $i++)
			$array[$i] = 'item'.$i;
		$p = new Paginator($array, count($array), 1, 1);
		$presenter = new BootstrapPresenter($p);

		$this->assertEquals(trim(file_get_contents(__DIR__.'/fixtures/first_page.html')), $presenter->render());
	}

}
