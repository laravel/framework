<?php

namespace Illuminate\Tests\Pagination;

use Illuminate\Pagination\Cursor;
use Illuminate\Pagination\CursorPaginator;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

class CursorPaginatorTest extends TestCase
{
    public function testReturnsRelevantContextInformation()
    {
        $p = new CursorPaginator($array = [['id' => 1], ['id' => 2], ['id' => 3]], 2, null, [
            'parameters' => ['id'],
        ]);

        $this->assertTrue($p->hasPages());
        $this->assertTrue($p->hasMorePages());
        $this->assertEquals([['id' => 1], ['id' => 2]], $p->items());

        $pageInfo = [
            'data' => [['id' => 1], ['id' => 2]],
            'path' => '/',
            'per_page' => 2,
            'next_cursor' => $this->getCursor(['id' => 2]),
            'next_page_url' => '/?cursor='.$this->getCursor(['id' => 2]),
            'prev_cursor' => null,
            'prev_page_url' => null,
        ];

        $this->assertEquals($pageInfo, $p->toArray());
    }

    public function testPaginatorRemovesTrailingSlashes()
    {
        $p = new CursorPaginator($array = [['id' => 4], ['id' => 5], ['id' => 6]], 2, null,
            ['path' => 'http://website.com/test/', 'parameters' => ['id']]);

        $this->assertSame('http://website.com/test?cursor='.$this->getCursor(['id' => 5]), $p->nextPageUrl());
    }

    public function testPaginatorGeneratesUrlsWithoutTrailingSlash()
    {
        $p = new CursorPaginator($array = [['id' => 4], ['id' => 5], ['id' => 6]], 2, null,
            ['path' => 'http://website.com/test', 'parameters' => ['id']]);

        $this->assertSame('http://website.com/test?cursor='.$this->getCursor(['id' => 5]), $p->nextPageUrl());
    }

    public function testItRetrievesThePaginatorOptions()
    {
        $p = new CursorPaginator($array = [['id' => 4], ['id' => 5], ['id' => 6]], 2, null,
            $options = ['path' => 'http://website.com/test', 'parameters' => ['id']]);

        $this->assertSame($p->getOptions(), $options);
    }

    public function testPaginatorReturnsPath()
    {
        $p = new CursorPaginator($array = [['id' => 4], ['id' => 5], ['id' => 6]], 2, null,
            $options = ['path' => 'http://website.com/test', 'parameters' => ['id']]);

        $this->assertSame($p->path(), 'http://website.com/test');
    }

    public function testCanTransformPaginatorItems()
    {
        $p = new CursorPaginator($array = [['id' => 4], ['id' => 5], ['id' => 6]], 2, null,
            $options = ['path' => 'http://website.com/test', 'parameters' => ['id']]);

        $p->through(function ($item) {
            $item['zid'] = $item['id'] + 2;
            unset($item['id']);

            return $item;
        });

        $this->assertInstanceOf(CursorPaginator::class, $p);
        $this->assertSame([['zid' => 6], ['zid' => 7]], $p->items());
    }

    public function testCursorPaginatorOnFirstAndLastPage()
    {
        $paginator = new CursorPaginator([['id' => 1], ['id' => 2], ['id' => 3], ['id' => 4]], 2, null, [
            'parameters' => ['id'],
        ]);

        $this->assertTrue($paginator->onFirstPage());
        $this->assertFalse($paginator->onLastPage());

        $cursor = new Cursor(['id' => 3]);
        $paginator = new CursorPaginator([['id' => 3], ['id' => 4]], 2, $cursor, [
            'parameters' => ['id'],
        ]);

        $this->assertFalse($paginator->onFirstPage());
        $this->assertTrue($paginator->onLastPage());
    }

    public function testReturnEmptyCursorWhenItemsAreEmpty()
    {
        $cursor = new Cursor(['id' => 25], true);

        $p = new CursorPaginator(new Collection, 25, $cursor, [
            'path' => 'http://website.com/test',
            'cursorName' => 'cursor',
            'parameters' => ['id'],
        ]);

        $this->assertInstanceOf(CursorPaginator::class, $p);

        $this->assertSame([
            'data' => [],
            'path' => 'http://website.com/test',
            'per_page' => 25,
            'next_cursor' => null,
            'next_page_url' => null,
            'prev_cursor' => null,
            'prev_page_url' => null,
        ], $p->toArray());
    }

    public function testCanTransformOutput()
    {
        $p = new CursorPaginator([['id' => 4], ['id' => 5], ['id' => 6]], 2, null,
            ['path' => 'http://website.com/test', 'parameters' => ['id']]
        );

        $p->setTransformer(function ($item) {
            return [
                'slug' => 'slug-'.$item['id'],
                'name' => 'Test '.$item['id'],
            ];
        });

        $this->assertInstanceOf(CursorPaginator::class, $p);
        $this->assertEquals([
            'data' => [['slug' => 'slug-4', 'name' => 'Test 4'], ['slug' => 'slug-5', 'name' => 'Test 5']],
            'path' => 'http://website.com/test',
            'per_page' => 2,
            'next_cursor' => 'eyJpZCI6NSwiX3BvaW50c1RvTmV4dEl0ZW1zIjp0cnVlfQ',
            'next_page_url' => 'http://website.com/test?cursor=eyJpZCI6NSwiX3BvaW50c1RvTmV4dEl0ZW1zIjp0cnVlfQ',
            'prev_cursor' => null,
            'prev_page_url' => null,
        ], $p->toArray());
        $this->assertEquals([ ['id' => 4], ['id' => 5]], $p->items());
    }

    protected function getCursor($params, $isNext = true)
    {
        return (new Cursor($params, $isNext))->encode();
    }
}
