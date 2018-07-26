<?php

use Mockery as m;

class PaginationCustomPresenterTest extends TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testGetPageLinkWrapper()
	{
		$customPresenter = m::mock('Illuminate\Pagination\Presenter');
		$customPresenter->shouldReceive('getPageLinkWrapper')
			->once()
			->andReturnUsing(function($url, $page) {
				return '<a href="' . $url . '">' . $page . '</a>';
			});

		$this->assertEquals('<a href="http://laravel.com?page=1">1</a>', $customPresenter->getPageLinkWrapper('http://laravel.com?page=1', '1', null));
	}


	public function testGetDisabledTextWrapper()
	{
		$customPresenter = m::mock('Illuminate\Pagination\Presenter');
		$customPresenter->shouldReceive('getDisabledTextWrapper')
			->once()
			->andReturnUsing(function($text) {
				return '<li class="bar">' . $text . '</li>';
			});
		$this->assertEquals('<li class="bar">foo</li>', $customPresenter->getDisabledTextWrapper('foo'));
	}


	public function testGetActiveTextWrapper()
	{
		$customPresenter = m::mock('Illuminate\Pagination\Presenter');
		$customPresenter->shouldReceive('getActiveTextWrapper')
			->once()
			->andReturnUsing(function($text) {
				return '<li class="baz">' . $text . '</li>';
			});
		$this->assertEquals('<li class="baz">bazzer</li>', $customPresenter->getActiveTextWrapper('bazzer'));
	}

}
