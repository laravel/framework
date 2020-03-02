<?php

namespace Illuminate\Tests\Pagination;

use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\AbstractPaginator;
use Illuminate\Support\Collection as BaseCollection;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class PaginatorLoadMorphTest extends TestCase
{
    /**
     * @var array
     */
    protected $relations;

    protected function setUp(): void
    {
        parent::setUp();

        $this->relations = [
            'App\\User' => 'photos',
            'App\\Company' => [
                'employees',
                'calendars',
            ],
        ];
    }

    public function testEloquentCollectionLoadMorphCanChainOnThePaginator()
    {
        $items = m::mock(Collection::class);
        $items->shouldReceive('loadMorph')->once()->with('parentable', $this->relations);

        $p = (new class extends AbstractPaginator {
            //
        })->setCollection($items);

        $this->assertSame($p, $p->loadMorph('parentable', $this->relations));
    }

    public function testSupportCollectionCannotLoadMorph()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('loadMorph method does not exist for this Collection.');

        $items = m::mock(BaseCollection::class);
        $items->shouldNotReceive('loadMorph')->once();

        $p = (new class extends AbstractPaginator {
            //
        })->setCollection($items);

        $p->loadMorph('parentable', $this->relations);
    }
}
