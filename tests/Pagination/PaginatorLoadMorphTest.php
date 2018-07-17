<?php

namespace Illuminate\Tests\Pagination;

use Mockery;
use PHPUnit\Framework\TestCase;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\AbstractPaginator;

class PaginatorLoadMorphTest extends TestCase
{
    public function testCollectionLoadMorphCanChainOnThePaginator()
    {
        $relations = [
            'App\\User' => 'photos',
            'App\\Company' => ['employees', 'calendars'],
        ];

        $items = Mockery::mock(Collection::class);
        $items->shouldReceive('loadMorph')->once()->with('parentable', $relations);

        $p = (new class extends AbstractPaginator {
        })->setCollection($items);

        $this->assertSame($p, $p->loadMorph('parentable', $relations));
    }
}
