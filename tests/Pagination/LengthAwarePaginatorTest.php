<?php

namespace Illuminate\Tests\Pagination;

use PHPUnit\Framework\TestCase;
use Illuminate\Pagination\LengthAwarePaginator;

class LengthAwarePaginatorTest extends TestCase
{
    public function setUp()
    {
        $this->p = new LengthAwarePaginator($array = ['item1', 'item2', 'item3', 'item4'], 4, 2, 2);
    }

    public function tearDown()
    {
        unset($this->p);
    }

    public function testLengthAwarePaginatorGetAndSetPageName()
    {
        $this->assertEquals('page', $this->p->getPageName());

        $this->p->setPageName('p');
        $this->assertEquals('p', $this->p->getPageName());
    }

    public function testLengthAwarePaginatorCanGiveMeRelevantPageInformation()
    {
        $this->assertEquals(2, $this->p->lastPage());
        $this->assertEquals(2, $this->p->currentPage());
        $this->assertTrue($this->p->hasPages());
        $this->assertFalse($this->p->hasMorePages());
        $this->assertEquals(['item1', 'item2', 'item3', 'item4'], $this->p->items());
    }

    public function testLengthAwarePaginatorCanGenerateUrls()
    {
        $this->p->setPath('http://website.com');
        $this->p->setPageName('foo');

        $this->assertEquals('http://website.com?foo=2',
                            $this->p->url($this->p->currentPage()));

        $this->assertEquals('http://website.com?foo=1',
                            $this->p->url($this->p->currentPage() - 1));

        $this->assertEquals('http://website.com?foo=1',
                            $this->p->url($this->p->currentPage() - 2));
    }

    public function testLengthAwarePaginatorCanGenerateUrlsWithQuery()
    {
        $this->p->setPath('http://website.com?sort_by=date');
        $this->p->setPageName('foo');

        $this->assertEquals('http://website.com?sort_by=date&foo=2',
                            $this->p->url($this->p->currentPage()));
    }

    public function testLengthAwarePaginatorCanGenerateUrlsWithoutTrailingSlashes()
    {
        $this->p->setPath('http://website.com/test');
        $this->p->setPageName('foo');

        $this->assertEquals('http://website.com/test?foo=2',
                            $this->p->url($this->p->currentPage()));

        $this->assertEquals('http://website.com/test?foo=1',
                            $this->p->url($this->p->currentPage() - 1));

        $this->assertEquals('http://website.com/test?foo=1',
                            $this->p->url($this->p->currentPage() - 2));
    }
}
