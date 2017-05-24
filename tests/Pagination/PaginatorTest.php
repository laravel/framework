<?php

namespace Illuminate\Tests\Pagination;

use PHPUnit\Framework\TestCase;
use Illuminate\Pagination\Paginator;

class PaginatorTest extends TestCase
{
    public function testSimplePaginatorReturnsRelevantContextInformation()
    {
        $p = new Paginator($array = ['item3', 'item4', 'item5'], 2, 2);

        $this->assertEquals(2, $p->currentPage());
        $this->assertTrue($p->hasPages());
        $this->assertTrue($p->hasMorePages());
        $this->assertEquals(['item3', 'item4'], $p->items());

        $pageInfo = [
                    'per_page' => 2,
                    'current_page' => 2,
                    'next_page_url' => '/?page=3',
                    'prev_page_url' => '/?page=1',
                    'from' => 3,
                    'to' => 4,
                    'data' => ['item3', 'item4'],
                    'path' => '/',
                    ];

        $this->assertEquals($pageInfo, $p->toArray());
    }

    public function testPaginatorRemovesTrailingSlashes()
    {
        $p = new Paginator($array = ['item1', 'item2', 'item3'], 2, 2,
                                    ['path' => 'http://website.com/test/']);

        $this->assertEquals('http://website.com/test?page=1', $p->previousPageUrl());
    }

    public function testPaginatorGeneratesUrlsWithoutTrailingSlash()
    {
        $p = new Paginator($array = ['item1', 'item2', 'item3'], 2, 2,
                                    ['path' => 'http://website.com/test']);

        $this->assertEquals('http://website.com/test?page=1', $p->previousPageUrl());
    }
}
