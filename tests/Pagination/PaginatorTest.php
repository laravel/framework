<?php

namespace Illuminate\Tests\Pagination;

use Generator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PaginatorTest extends TestCase
{
    protected function tearDown(): void
    {
        Paginator::setDefaultMaxPerPage(null);
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

    #[DataProvider('invalidPerPageProvider')]
    public function testPerPageIsClampedToAMinimumOfOne($perPage): void
    {
        $p = new Paginator(
            items: new Collection(['item1', 'item2']),
            perPage: $perPage,
            currentPage: 1,
        );

        $this->assertSame(1, $p->perPage());
        $this->assertCount(1, $p->items());
        $this->assertTrue($p->hasMorePages());
    }

    public static function invalidPerPageProvider(): Generator
    {
        yield 'zero' => [0];
        yield 'float' => [0.5];
        yield 'negative' => [-1];
    }

    public function testPerPageIsClampedToTheInstanceMax(): void
    {
        $p = new Paginator(
            items: new Collection(['item1', 'item2', 'item3']),
            perPage: 10,
            currentPage: 1,
            options: ['maxPerPage' => 2],
        );

        $this->assertSame(2, $p->perPage());
    }

    public function testPerPageIsClampedToTheDefaultMax(): void
    {
        Paginator::setDefaultMaxPerPage(5);

        $p = new Paginator(
            items: new Collection(['item1', 'item2', 'item3']),
            perPage: 10,
            currentPage: 1,
        );

        $this->assertSame(5, $p->perPage());
    }

    public function testInstanceMaxTakesPrecedenceOverDefaultMax(): void
    {
        Paginator::setDefaultMaxPerPage(5);

        $p = new Paginator(
            items: new Collection(['item1', 'item2', 'item3']),
            perPage: 10,
            currentPage: 1,
            options: ['maxPerPage' => 2],
        );

        $this->assertSame(2, $p->perPage());
    }

    public function testPerPageIsNotClampedWhenNoMaxIsSet(): void
    {
        $p = new Paginator(
            items: new Collection(['item1', 'item2', 'item3']),
            perPage: 10,
            currentPage: 1,
        );

        $this->assertSame(10, $p->perPage());
    }

    #[DataProvider('invalidMaxProvider')]
    public function testInstanceMaxIsFlooredToOneWhenInvalid($max): void
    {
        $p = new Paginator(
            items: new Collection(['item1', 'item2', 'item3']),
            perPage: 10,
            currentPage: 1,
            options: ['maxPerPage' => $max],
        );

        $this->assertSame(1, $p->perPage());
    }

    #[DataProvider('invalidMaxProvider')]
    public function testDefaultMaxIsFlooredToOneWhenInvalid($max): void
    {
        Paginator::setDefaultMaxPerPage($max);

        $p = new Paginator(
            items: new Collection(['item1', 'item2', 'item3']),
            perPage: 10,
            currentPage: 1,
        );

        $this->assertSame(1, $p->perPage());
    }

    public static function invalidMaxProvider(): Generator
    {
        yield 'zero' => [0];
        yield 'float' => [0.5];
        yield 'negative' => [-5];
    }

    public function testMinimumAndMaximumClampsCanCollide(): void
    {
        $p = new Paginator(
            items: new Collection(['item1', 'item2', 'item3']),
            perPage: 0,
            currentPage: 1,
            options: ['maxPerPage' => 1],
        );

        $this->assertSame(1, $p->perPage());
    }
}
