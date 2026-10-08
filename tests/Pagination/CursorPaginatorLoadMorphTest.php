<?php

namespace Illuminate\Tests\Pagination;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\AbstractCursorPaginator;
use JMac\Testing\Double;
use JMac\Testing\Integrations\PHPUnit\VerifiesDoubles;
use PHPUnit\Framework\TestCase;

class CursorPaginatorLoadMorphTest extends TestCase
{
    use VerifiesDoubles;

    public function testCollectionLoadMorphCanChainOnThePaginator()
    {
        $relations = [
            'App\\User' => 'photos',
            'App\\Company' => ['employees', 'calendars'],
        ];

        $items = Double::for(Collection::class);
        $items->expects('loadMorph')->with('parentable', $relations);

        $p = (new class extends AbstractCursorPaginator {
        })->setCollection($items);

        $this->assertSame($p, $p->loadMorph('parentable', $relations));
    }
}
