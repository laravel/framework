<?php

namespace Illuminate\Tests\Pagination;

use Generator;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class LengthAwarePaginatorTest extends TestCase
{
    /**
     * @var \Illuminate\Pagination\LengthAwarePaginator
     */
    private $p;

    /**
     * @var array
     */
    private $options;

    protected function setUp(): void
    {
        $this->options = ['onEachSide' => 5];
        $this->p = new LengthAwarePaginator($array = ['item1', 'item2', 'item3', 'item4'], 4, 2, 2, $this->options);
    }

    protected function tearDown(): void
    {
        unset($this->p);

        LengthAwarePaginator::setDefaultMaxPerPage(null);
    }

    public function testLengthAwarePaginatorGetAndSetPageName()
    {
        $this->assertSame('page', $this->p->getPageName());

        $this->p->setPageName('p');
        $this->assertSame('p', $this->p->getPageName());
    }

    public function testLengthAwarePaginatorCanGiveMeRelevantPageInformation()
    {
        $this->assertEquals(2, $this->p->lastPage());
        $this->assertEquals(2, $this->p->currentPage());
        $this->assertTrue($this->p->hasPages());
        $this->assertFalse($this->p->hasMorePages());
        $this->assertEquals(['item1', 'item2', 'item3', 'item4'], $this->p->items());
    }

    public function testLengthAwarePaginatorSetCorrectInformationWithNoItems()
    {
        $paginator = new LengthAwarePaginator([], 0, 2, 1);

        $this->assertEquals(1, $paginator->lastPage());
        $this->assertEquals(1, $paginator->currentPage());
        $this->assertFalse($paginator->hasPages());
        $this->assertFalse($paginator->hasMorePages());
        $this->assertEmpty($paginator->items());
    }

    public function testLengthAwarePaginatorDoesNotDivideByZeroWhenPerPageIsZero(): void
    {
        $paginator = new LengthAwarePaginator(['item1', 'item2', 'item3', 'item4'], 4, 0, 1);

        $this->assertSame(4, $paginator->lastPage());
        $this->assertSame(1, $paginator->perPage());
    }

    #[DataProvider('invalidPerPageProvider')]
    public function testPerPageIsClampedToAMinimumOfOne($perPage): void
    {
        $paginator = new LengthAwarePaginator(
            items: new Collection(['item1', 'item2', 'item3', 'item4']),
            total: 4,
            perPage: $perPage,
            currentPage: 1,
        );

        $this->assertSame(1, $paginator->perPage());
    }

    public static function invalidPerPageProvider(): Generator
    {
        yield 'zero' => [0];
        yield 'float' => [0.5];
        yield 'negative' => [-1];
    }

    public function testPerPageIsClampedToTheInstanceMax(): void
    {
        $paginator = new LengthAwarePaginator(
            items: new Collection(['item1', 'item2', 'item3']),
            total: 3,
            perPage: 10,
            currentPage: 1,
            options: ['maxPerPage' => 2],
        );

        $this->assertSame(2, $paginator->perPage());
    }

    public function testPerPageIsClampedToTheDefaultMax(): void
    {
        LengthAwarePaginator::setDefaultMaxPerPage(5);

        $paginator = new LengthAwarePaginator(
            items: new Collection(['item1', 'item2', 'item3']),
            total: 3,
            perPage: 10,
            currentPage: 1,
        );

        $this->assertSame(5, $paginator->perPage());
    }

    public function testInstanceMaxTakesPrecedenceOverDefaultMax(): void
    {
        LengthAwarePaginator::setDefaultMaxPerPage(5);

        $paginator = new LengthAwarePaginator(
            items: new Collection(['item1', 'item2', 'item3']),
            total: 3,
            perPage: 10,
            currentPage: 1,
            options: ['maxPerPage' => 2],
        );

        $this->assertSame(2, $paginator->perPage());
    }

    public function testLengthAwarePaginatorOnFirstAndLastPage()
    {
        $paginator = new LengthAwarePaginator(['1', '2', '3', '4'], 4, 2, 2);

        $this->assertTrue($paginator->onLastPage());
        $this->assertFalse($paginator->onFirstPage());

        $paginator = new LengthAwarePaginator(['1', '2', '3', '4'], 4, 2, 1);

        $this->assertFalse($paginator->onLastPage());
        $this->assertTrue($paginator->onFirstPage());
    }

    public function testLengthAwarePaginatorCanGenerateUrls()
    {
        $this->p->setPath('http://website.com');
        $this->p->setPageName('foo');

        $this->assertSame(
            'http://website.com',
            $this->p->path()
        );

        $this->assertSame(
            'http://website.com?foo=2',
            $this->p->url($this->p->currentPage())
        );

        $this->assertSame(
            'http://website.com?foo=1',
            $this->p->url($this->p->currentPage() - 1)
        );

        $this->assertSame(
            'http://website.com?foo=1',
            $this->p->url($this->p->currentPage() - 2)
        );
    }

    public function testLengthAwarePaginatorCanGenerateUrlsWithQuery()
    {
        $this->p->setPath('http://website.com?sort_by=date');
        $this->p->setPageName('foo');

        $this->assertSame(
            'http://website.com?sort_by=date&foo=2',
            $this->p->url($this->p->currentPage())
        );
    }

    public function testLengthAwarePaginatorCanGenerateUrlsWithoutTrailingSlashes()
    {
        $this->p->setPath('http://website.com/test');
        $this->p->setPageName('foo');

        $this->assertSame(
            'http://website.com/test?foo=2',
            $this->p->url($this->p->currentPage())
        );

        $this->assertSame(
            'http://website.com/test?foo=1',
            $this->p->url($this->p->currentPage() - 1)
        );

        $this->assertSame(
            'http://website.com/test?foo=1',
            $this->p->url($this->p->currentPage() - 2)
        );
    }

    public function testLengthAwarePaginatorCorrectlyGenerateUrlsWithQueryAndSpaces()
    {
        $this->p->setPath('http://website.com?key=value%20with%20spaces');
        $this->p->setPageName('foo');

        $this->assertSame(
            'http://website.com?key=value%20with%20spaces&foo=2',
            $this->p->url($this->p->currentPage())
        );
    }

    public function testItRetrievesThePaginatorOptions()
    {
        $this->assertSame($this->options, $this->p->getOptions());
    }
}
