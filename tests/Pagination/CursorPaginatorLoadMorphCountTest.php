<?php

namespace Illuminate\Tests\Pagination;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\AbstractCursorPaginator;
use Mockery;
use PHPUnit\Framework\TestCase;

class CursorPaginatorLoadMorphCountTest extends TestCase
{
    public function testCollectionLoadMorphCountCanChainOnThePaginator()
    {
        $relations = [
            'App\\User' => 'photos',
            'App\\Company' => ['employees', 'calendars'],
        ];

        $items = Mockery::mock(Collection::class);
        $items->expects('loadMorphCount')->with('parentable', $relations);

        $p = (new class extends AbstractCursorPaginator {
        })->setCollection($items);

        $this->assertSame($p, $p->loadMorphCount('parentable', $relations));
    }
}
