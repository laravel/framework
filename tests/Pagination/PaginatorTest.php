<?php

namespace Illuminate\Tests\Pagination;

use Illuminate\Pagination\AbstractPaginator;
use Illuminate\Pagination\Paginator;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

class PaginatorTest extends TestCase
{
    protected function tearDown(): void
    {
        // Reset the static query string resolver so it does not leak into other tests.
        (new ReflectionProperty(AbstractPaginator::class, 'queryStringResolver'))->setValue(null, null);

        parent::tearDown();
    }

    public function testWithQueryStringControlsWhetherTheQueryStringIsAppended()
    {
        Paginator::queryStringResolver(fn () => ['sort' => 'name', 'status' => 'active']);

        // the resolved query string is dropped from the generated URLs
        $p = new Paginator(['item1', 'item2', 'item3'], 2, 2, ['path' => 'http://website.com/test']);
        $this->assertSame('http://website.com/test?page=1', $p->previousPageUrl());

        // the same query string is now appended to the URLs
        $p = new Paginator(['item1', 'item2', 'item3'], 2, 2, ['path' => 'http://website.com/test']);
        $p->withQueryString();
        $this->assertSame('http://website.com/test?sort=name&status=active&page=1', $p->previousPageUrl());
    }

    public function testWithQueryStringExcludesThePageNameFromTheQueryString()
    {
        Paginator::queryStringResolver(fn () => ['page' => 5, 'sort' => 'name']);

        $p = new Paginator(['item1', 'item2', 'item3'], 2, 2, ['path' => 'http://website.com/test']);
        $p->withQueryString();

        $this->assertSame('http://website.com/test?sort=name&page=1', $p->previousPageUrl());
    }

    public function testWithQueryStringIsANoOpWhenNoResolverIsSet()
    {
        $p = new Paginator(['item1', 'item2', 'item3'], 2, 2, ['path' => 'http://website.com/test']);

        $this->assertSame($p, $p->withQueryString());
        $this->assertSame('http://website.com/test?page=1', $p->previousPageUrl());
    }

    public function testSimplePaginatorReturnsRelevantContextInformation()
    {
        /** @var Paginator<int, string> $p */
        $p = new Paginator(['item3', 'item4', 'item5'], 2, 2);

        $this->assertEquals(2, $p->currentPage());
        $this->assertTrue($p->hasPages());
        $this->assertTrue($p->hasMorePages());
        $this->assertEquals(['item3', 'item4'], $p->items());

        $pageInfo = [
            'per_page' => 2,
            'current_page' => 2,
            'first_page_url' => '/?page=1',
            'current_page_url' => '/?page=2',
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
        $p = new Paginator(['item1', 'item2', 'item3'], 2, 2, ['path' => 'http://website.com/test/']);

        $this->assertSame('http://website.com/test?page=1', $p->previousPageUrl());
    }

    public function testPaginatorGeneratesUrlsWithoutTrailingSlash()
    {
        $p = new Paginator(['item1', 'item2', 'item3'], 2, 2, ['path' => 'http://website.com/test']);

        $this->assertSame('http://website.com/test?page=1', $p->previousPageUrl());
    }

    public function testItRetrievesThePaginatorOptions()
    {
        $p = new Paginator(['item1', 'item2', 'item3'], 2, 2, ['path' => 'http://website.com/test']);

        $this->assertSame(['path' => 'http://website.com/test'], $p->getOptions());
    }

    public function testPaginatorReturnsPath()
    {
        $p = new Paginator(['item1', 'item2', 'item3'], 2, 2, ['path' => 'http://website.com/test']);

        $this->assertSame('http://website.com/test', $p->path());
    }

    public function testCanTransformPaginatorItems()
    {
        $p = new Paginator(['item1', 'item2', 'item3'], 3, 1, ['path' => 'http://website.com/test']);

        $p->through(function ($item) {
            return substr($item, 4, 1);
        });

        $this->assertInstanceOf(Paginator::class, $p);
        $this->assertSame(['1', '2', '3'], $p->items());
    }

    public function testPaginatorToJson()
    {
        $p = new Paginator(['item1', 'item2', 'item3'], 3, 1);
        $results = $p->toJson();
        $expected = json_encode($p->toArray());

        $this->assertJsonStringEqualsJsonString($expected, $results);
        $this->assertSame($expected, $results);
    }

    public function testPaginatorToPrettyJson()
    {
        $p = new Paginator(['item/1', 'item/2', 'item/3'], 3, 1);
        $results = $p->toPrettyJson();
        $expected = $p->toJson(JSON_PRETTY_PRINT);

        $this->assertJsonStringEqualsJsonString($expected, $results);
        $this->assertSame($expected, $results);
        $this->assertStringContainsString("\n", $results);
        $this->assertStringContainsString('    ', $results);
        $this->assertStringContainsString('item\/1', $results);

        $results = $p->toPrettyJson(JSON_UNESCAPED_SLASHES);
        $this->assertStringContainsString("\n", $results);
        $this->assertStringContainsString('    ', $results);
        $this->assertStringContainsString('item/1', $results);
    }
}
