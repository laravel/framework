<?php

namespace Illuminate\Tests\Pagination;

use PHPUnit\Framework\TestCase;
use Illuminate\Pagination\Paginator;

class PaginatorTest extends TestCase
{
    public function testSimplePaginatorReturnsRelevantContextInformation()
    {
        $p = new Paginator($array = ['item3', 'item4', 'item5'], 2, 3);

        $this->assertEquals(3, $p->currentPage());
        $this->assertTrue($p->hasPages());
        $this->assertTrue($p->hasMorePages());
        $this->assertEquals(['item3', 'item4'], $p->items());

        $pageInfo = [
            'per_page' => 2,
            'current_page' => 3,
            'first_page_url' => '/',
            'next_page_url' => '/?page=4',
            'prev_page_url' => '/?page=2',
            'from' => 5,
            'to' => 6,
            'data' => ['item3', 'item4'],
            'path' => '/',
        ];

        $this->assertEquals($pageInfo, $p->toArray());
    }

    public function testPaginatorRemovesTrailingSlashes()
    {
        $p = new Paginator($array = ['item1', 'item2', 'item3'], 2, 3,
                                    ['path' => 'http://website.com/test/']);

        $this->assertEquals('http://website.com/test?page=2', $p->previousPageUrl());
    }

    public function testPaginatorGeneratesUrlsWithoutTrailingSlash()
    {
        $p = new Paginator($array = ['item1', 'item2', 'item3'], 2, 3,
                                    ['path' => 'http://website.com/test']);

        $this->assertEquals('http://website.com/test?page=2', $p->previousPageUrl());
    }
}
