<?php

use Mockery as m;
use Illuminate\Pagination\UrlWindow;
use Illuminate\Pagination\AbstractPaginator;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator as Paginator;
use Illuminate\Pagination\BootstrapThreePresenter as BootstrapPresenter;

class PaginationLengthAwarePaginatorTest extends PHPUnit_Framework_TestCase
{
    protected $allItems;
    protected $visibleSliceOfItems;
    protected $perPage;
    protected $totalNumberOfItems;

    public function setUp()
    {
        parent::setUp();
        $this->allItems = ['item1','item2','item3', 'item4', 'item5', 'item6'];
        // The currently visible items. We're at the end of the pagination.
        $this->visibleSliceOfItems = ['item4', 'item5', 'item6'];
        // Items to show per page
        $this->perPage = count($this->visibleSliceOfItems);
        // The total number of items – 6 in our case
        $this->totalNumberOfItems = count($this->allItems);
    }

    public function tearDown()
    {
        m::close();
    }

    public function testPaginatorReturnsCurrentPageCorrectly()
    {
        $currentPage = 2;
        $paginator = new LengthAwarePaginator(
            $this->visibleSliceOfItems,
            $this->totalNumberOfItems,
            $this->perPage,
            $currentPage
        );
        $this->assertEquals($currentPage, $paginator->currentPage());
    }

    public function testPaginatorDoesNotExceedBoundariesEvenIfAskedToDoSo()
    {
        // exceeds boundary of pagination
        $currentPage = 3;
        $expectedLastPage = 2;
        $paginator = new LengthAwarePaginator(
            $this->visibleSliceOfItems,
            $this->totalNumberOfItems,
            $this->perPage,
            $currentPage
        );
        $this->assertEquals($expectedLastPage, $paginator->currentPage());
    }
}
