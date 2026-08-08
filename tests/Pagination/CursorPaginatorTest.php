<?php

namespace Illuminate\Tests\Pagination;

use Illuminate\Pagination\Cursor;
use Illuminate\Pagination\CursorPaginator;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

class CursorPaginatorTest extends TestCase
{
    protected function tearDown(): void
    {
        CursorPaginator::setDefaultMaxPerPage(null);
    }

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

        $this->assertSame('http://website.com/test', $p->path());
    }

    public function testCanTransformPaginatorItems()
    {
        $p = new CursorPaginator($array = [['id' => 4], ['id' => 5], ['id' => 6]], 2, null,
            $options = ['path' => 'http://website.com/test', 'parameters' => ['id']]);

        $p->through(function ($item) {
            $item['id'] = $item['id'] + 2;

            return $item;
        });

        $this->assertInstanceOf(CursorPaginator::class, $p);
        $this->assertSame([['id' => 6], ['id' => 7]], $p->items());
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

    public function testItemsAreConsistentlyReindexedForNextAndPreviousPages(): void
    {
        $next = new CursorPaginator([['id' => 1], ['id' => 2], ['id' => 3]], 2, null, [
            'parameters' => ['id'],
        ]);

        $this->assertSame([0, 1], array_keys($next->items()));

        $cursor = new Cursor(['id' => 5], false);

        $previous = new CursorPaginator([['id' => 4], ['id' => 3], ['id' => 2]], 2, $cursor, [
            'parameters' => ['id'],
        ]);

        $this->assertSame([0, 1], array_keys($previous->items()));
        $this->assertSame([['id' => 3], ['id' => 4]], $previous->items());
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

    public function testCursorPaginatorToJson()
    {
        $paginator = new CursorPaginator([['id' => 1], ['id' => 2], ['id' => 3], ['id' => 4]], 2, null);
        $results = $paginator->toJson();
        $expected = json_encode($paginator->toArray());

        $this->assertJsonStringEqualsJsonString($expected, $results);
        $this->assertSame($expected, $results);
    }

    public function testCursorPaginatorToPrettyJson()
    {
        $paginator = new CursorPaginator([['id' => '1'], ['id' => '2'], ['id' => '3'], ['id' => '4']], 2, null);
        $results = $paginator->toPrettyJson();
        $expected = $paginator->toJson(JSON_PRETTY_PRINT);

        $this->assertJsonStringEqualsJsonString($expected, $results);
        $this->assertSame($expected, $results);
        $this->assertStringContainsString("\n", $results);
        $this->assertStringContainsString('    ', $results);

        $results = $paginator->toPrettyJson(JSON_NUMERIC_CHECK);
        $this->assertStringContainsString("\n", $results);
        $this->assertStringContainsString('    ', $results);
        $this->assertStringContainsString('"id": 1', $results);
    }

    public function testPerPageIsClampedToAMinimumOfOne(): void
    {
        $p = new CursorPaginator([['id' => 1], ['id' => 2]], 0, null);
        $this->assertSame(1, $p->perPage());

        $p = new CursorPaginator([['id' => 1], ['id' => 2]], 0.5, null);
        $this->assertSame(1, $p->perPage());

        $p = new CursorPaginator([['id' => 1], ['id' => 2]], -1, null);
        $this->assertSame(1, $p->perPage());
    }

    public function testPerPageIsClampedToTheInstanceMax(): void
    {
        $p = new CursorPaginator([['id' => 1], ['id' => 2]], 10, null, ['maxPerPage' => 2]);

        $this->assertSame(2, $p->perPage());
    }

    public function testPerPageIsClampedToTheDefaultMax(): void
    {
        CursorPaginator::setDefaultMaxPerPage(5);

        $p = new CursorPaginator([['id' => 1], ['id' => 2]], 10, null);

        $this->assertSame(5, $p->perPage());
    }

    public function testInstanceMaxTakesPrecedenceOverDefaultMax(): void
    {
        CursorPaginator::setDefaultMaxPerPage(5);

        $p = new CursorPaginator([['id' => 1], ['id' => 2]], 10, null, ['maxPerPage' => 2]);

        $this->assertSame(2, $p->perPage());
    }

    protected function getCursor($params, $isNext = true)
    {
        return (new Cursor($params, $isNext))->encode();
    }
}
