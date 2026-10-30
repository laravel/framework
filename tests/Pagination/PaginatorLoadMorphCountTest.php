<?php

namespace Illuminate\Tests\Pagination;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\AbstractPaginator;
use Mockery;
use PHPUnit\Framework\TestCase;

class PaginatorLoadMorphCountTest extends TestCase
{
    public function testCollectionLoadMorphCountCanChainOnThePaginator()
    {
        $relations = [
            'App\\User' => 'photos',
            'App\\Company' => ['employees', 'calendars'],
        ];

        $items = Mockery::mock(Collection::class);
        $items->expects('loadMorphCount')->with('parentable', $relations);

        $p = (new class extends AbstractPaginator {
        })->setCollection($items);

        $this->assertSame($p, $p->loadMorphCount('parentable', $relations));
    }
}
