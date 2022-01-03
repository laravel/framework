<?php

namespace Illuminate\Tests\Integration\Database\EloquentWithPaginationTest;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\RelationNotFoundException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Pagination\Cursor;
use Illuminate\Pagination\CursorPaginator;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Schema;
use Illuminate\Tests\Integration\Database\DatabaseTestCase;

class EloquentWithPaginationTest extends DatabaseTestCase
{
    protected function defineDatabaseMigrationsAfterDatabaseRefreshed()
    {
        Schema::create('one', function (Blueprint $table) {
            $table->increments('id');
        });

        Schema::create('two', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('one_id');
        });

        Schema::create('three', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('two_id');
        });

        Schema::create('four', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('one_id');
        });

        Schema::create('five', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('one_id');
        });

        Schema::create('six', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('five_id');
        });

        Model1::query()->insert([[], []]);

        Model2::query()->insert([
            ['one_id' => 1],
            ['one_id' => 1],
            ['one_id' => 1],
            ['one_id' => 1],
        ]);
        Model3::query()->insert([
            ['two_id' => 2],
            ['two_id' => 2],
            ['two_id' => 3],
            ['two_id' => 3],
            ['two_id' => 3],
        ]);
        Model4::query()->insert([
            ['one_id' => 1],
            ['one_id' => 1],
            ['one_id' => 1],
        ]);
        Model5::query()->insert(['one_id' => 2]);
        Model6::query()->insert([
            ['five_id' => 1],
            ['five_id' => 1],
        ]);
    }

    public function testPaginatedOnlyListFirstLevelRelation()
    {
        $result = Model1::withPaged('twos.threes');

        $this->assertArrayHasKey('twos', $result->getPaginatedEagerLoads());
        $this->assertArrayNotHasKey('twos.threes', $result->getPaginatedEagerLoads());
        $this->assertArrayNotHasKey('threes', $result->getPaginatedEagerLoads());
    }

    public function testPaginatedRelation()
    {
        $result = Model1::withPaged('twos')->first();

        $this->assertInstanceOf(LengthAwarePaginator::class, $result->twos);
        $this->assertCount(4, $result->twos);
        $this->assertInstanceOf(Model2::class, $result->twos->first());
        $this->assertSame('twos_page', $result->twos->getPageName());
        $this->assertSame(1, $result->twos->currentPage());
    }

    public function testPaginatesOnlyFirstLevelRelation()
    {
        $result = Model1::withPaged('twos.threes')->first();

        $this->assertInstanceOf(LengthAwarePaginator::class, $result->twos);
        $this->assertCount(4, $result->twos);
        $this->assertInstanceOf(Model2::class, $result->twos->first());
        $this->assertSame('twos_page', $result->twos->getPageName());
        $this->assertSame(1, $result->twos->currentPage());

        $this->assertInstanceOf(Collection::class, $result->twos->first()->getRelation('threes'));
        $this->assertInstanceOf(Collection::class, $result->twos->get(1)->getRelation('threes'));
        $this->assertCount(2, $result->twos->get(1)->getRelation('threes'));
    }

    public function testPaginatesWithShorthand()
    {
        $result = Model1::withPaged('twos', 2, 2, 'foo')->first();

        $this->assertInstanceOf(LengthAwarePaginator::class, $result->twos);
        $this->assertCount(2, $result->twos);
        $this->assertSame(2, $result->twos->currentPage());
        $this->assertSame('foo', $result->twos->getPageName());
    }

    public function testPaginatesWithCallback()
    {
        $result = Model1::withPaged('twos', function ($query, $pagination) {
            $query->where('id', '>', 1);
            /** @var \Illuminate\Database\Eloquent\Pagination $pagination */
            $pagination->perPage(2, 2, 'foo');
        })->first();

        $this->assertInstanceOf(LengthAwarePaginator::class, $result->twos);
        $this->assertCount(1, $result->twos);
        $this->assertSame(2, $result->twos->currentPage());
        $this->assertSame('foo', $result->twos->getPageName());
    }

    public function testPaginatesMultipleRelations()
    {
        $result = Model1::withPaged('twos', 'fours', 'allFours')->first();

        $this->assertInstanceOf(LengthAwarePaginator::class, $result->twos);
        $this->assertCount(4, $result->twos);
        $this->assertInstanceOf(LengthAwarePaginator::class, $result->fours);
        $this->assertCount(2, $result->fours);
        $this->assertInstanceOf(LengthAwarePaginator::class, $result->allFours);
        $this->assertCount(3, $result->allFours);

        $this->assertSame('twos_page', $result->twos->getPageName());
        $this->assertSame('fours_page', $result->fours->getPageName());
        $this->assertSame('all_fours_page', $result->allFours->getPageName());
    }

    public function testPaginatesRelationsWithColumns()
    {
        $result = Model1::withPaged('twos:id', 'fours', 'allFours:id')->first();

        $this->assertNull($result->twos->first()->one_id);
        $this->assertSame(1, $result->fours->first()->one_id);
        $this->assertNull($result->allFours->first()->one_id);
    }

    public function testExceptionPaginatingRelationWithColumnsAndCallback()
    {
        $this->expectException(RelationNotFoundException::class);
        $this->expectExceptionMessage('Call to undefined relationship [twos:id] on model [Illuminate\Tests\Integration\Database\EloquentWithPaginationTest\Model1].');

        Model1::withPaged('twos:id', function ($query, $pagination) {
            $query->select('one_id');

            $pagination->pageName('foo');
        })->first();
    }

    public function testPaginatesSingleRelation()
    {
        $result = Model1::withPaged('five.sixes')->skip(1)->first();

        $this->assertInstanceOf(LengthAwarePaginator::class, $result->five);
        $this->assertInstanceOf(Collection::class, $result->five->first()->sixes);
    }

    /* Simple Pagination tests */

    public function testSimplePaginatedOnlyListFirstLevelRelation()
    {
        $result = Model1::withSimplePaged('twos.threes');

        $this->assertArrayHasKey('twos', $result->getPaginatedEagerLoads());
        $this->assertArrayNotHasKey('twos.threes', $result->getPaginatedEagerLoads());
        $this->assertArrayNotHasKey('threes', $result->getPaginatedEagerLoads());
    }

    public function testSimplePaginatedRelation()
    {
        $result = Model1::withSimplePaged('twos.threes')->first();

        $this->assertInstanceOf(Paginator::class, $result->twos);
        $this->assertCount(4, $result->twos);
        $this->assertInstanceOf(Model2::class, $result->twos->first());
        $this->assertSame('twos_page', $result->twos->getPageName());
        $this->assertSame(1, $result->twos->currentPage());

        $this->assertInstanceOf(Collection::class, $result->twos->first()->getRelation('threes'));
        $this->assertInstanceOf(Collection::class, $result->twos->get(1)->getRelation('threes'));
        $this->assertCount(2, $result->twos->get(1)->getRelation('threes'));
    }

    public function testSimplePaginatesWithShorthand()
    {
        $result = Model1::withSimplePaged('twos', 2, 2, 'foo')->first();

        $this->assertInstanceOf(Paginator::class, $result->twos);
        $this->assertCount(2, $result->twos);
        $this->assertSame(2, $result->twos->currentPage());
        $this->assertSame('foo', $result->twos->getPageName());
    }

    public function testSimplePaginatesWithCallback()
    {
        $result = Model1::withSimplePaged('twos', function ($query, $pagination) {
            $query->where('id', '>', 1);
            /** @var \Illuminate\Database\Eloquent\Pagination $pagination */
            $pagination->perPage(2, 2, 'foo');
        })->first();

        $this->assertInstanceOf(Paginator::class, $result->twos);
        $this->assertCount(1, $result->twos);
        $this->assertSame(2, $result->twos->currentPage());
        $this->assertSame('foo', $result->twos->getPageName());
    }

    public function testSimplePaginatesMultipleRelations()
    {
        $result = Model1::withSimplePaged('twos', 'fours', 'allFours')->first();

        $this->assertInstanceOf(Paginator::class, $result->twos);
        $this->assertCount(4, $result->twos);
        $this->assertInstanceOf(Paginator::class, $result->fours);
        $this->assertCount(2, $result->fours);
        $this->assertInstanceOf(Paginator::class, $result->allFours);
        $this->assertCount(3, $result->allFours);

        $this->assertSame('twos_page', $result->twos->getPageName());
        $this->assertSame('fours_page', $result->fours->getPageName());
        $this->assertSame('all_fours_page', $result->allFours->getPageName());
    }

    public function testSimplePaginatesRelationsWithColumns()
    {
        $result = Model1::withSimplePaged('twos:id', 'fours', 'allFours:id')->first();

        $this->assertNull($result->twos->first()->one_id);
        $this->assertSame(1, $result->fours->first()->one_id);
        $this->assertNull($result->allFours->first()->one_id);
    }

    public function testExceptionSimplePaginatingRelationWithColumnsAndCallback()
    {
        $this->expectException(RelationNotFoundException::class);
        $this->expectExceptionMessage('Call to undefined relationship [twos:id] on model [Illuminate\Tests\Integration\Database\EloquentWithPaginationTest\Model1].');

        Model1::withSimplePaged('twos:id', function ($query, $pagination) {
            $query->select('one_id');

            $pagination->pageName('foo');
        })->first();
    }

    public function testSimplePaginatesSingleRelation()
    {
        $result = Model1::withSimplePaged('five.sixes')->skip(1)->first();

        $this->assertInstanceOf(Paginator::class, $result->five);
        $this->assertInstanceOf(Collection::class, $result->five->first()->sixes);
    }

    /* Cursor Pagination tests */

    public function testCursorPaginatedOnlyListFirstLevelRelation()
    {
        $result = Model1::withCursorPaged('twos.threes');

        $this->assertArrayHasKey('twos', $result->getPaginatedEagerLoads());
        $this->assertArrayNotHasKey('twos.threes', $result->getPaginatedEagerLoads());
        $this->assertArrayNotHasKey('threes', $result->getPaginatedEagerLoads());
    }

    public function testCursorPaginatedRelation()
    {
        $result = Model1::withCursorPaged('twos.threes')->first();

        $this->assertInstanceOf(CursorPaginator::class, $result->twos);
        $this->assertCount(4, $result->twos);
        $this->assertInstanceOf(Model2::class, $result->twos->first());
        $this->assertSame('twos_page', $result->twos->getCursorName());
        $this->assertNull($result->twos->cursor());
        $this->assertNull($result->twos->previousCursor());
        $this->assertNull($result->twos->nextCursor());

        $this->assertInstanceOf(Collection::class, $result->twos->first()->getRelation('threes'));
        $this->assertInstanceOf(Collection::class, $result->twos->get(1)->getRelation('threes'));
        $this->assertCount(2, $result->twos->get(1)->getRelation('threes'));
    }

    public function testCursorPaginatesWithShorthandIgnoresCursor()
    {
        $result = Model1::withCursorPaged('twos', 2, 2, 'foo')->first();

        $this->assertInstanceOf(CursorPaginator::class, $result->twos);
        $this->assertCount(2, $result->twos);
        $this->assertNull($result->twos->cursor());
        $this->assertSame('foo', $result->twos->getCursorName());
        $this->assertTrue($result->twos->hasMorePages());

        $this->assertSame(
            'http://localhost?foo=eyJ0d28uaWQiOjIsIl9wb2ludHNUb05leHRJdGVtcyI6dHJ1ZX0',
            $result->twos->nextPageUrl()
        );

        $this->assertNull($result->twos->previousPageUrl());
    }

    public function testCursorPaginatesWithShorthandWithStringCursor()
    {
        $cursor = 'eyJ0d28uaWQiOjIsIl9wb2ludHNUb05leHRJdGVtcyI6dHJ1ZX0';

        $result = Model1::withCursorPaged('twos', 2, $cursor, 'foo')->first();

        $this->assertCount(2, $result->twos);
        $this->assertEquals(2, $result->twos->cursor()->parameter('two.id'));
        $this->assertFalse($result->twos->cursor()->pointsToPreviousItems());
        $this->assertTrue($result->twos->cursor()->pointsToNextItems());

        $this->assertSame(3, $result->twos->first()->getKey());
        $this->assertSame(4, $result->twos->last()->getKey());

        $this->assertSame('foo', $result->twos->getCursorName());
    }

    public function testCursorPaginatesWithShorthandWithCursor()
    {
        $cursor = new Cursor(['two.id' => 2], true);

        $result = Model1::withCursorPaged('twos', 2, $cursor, 'foo')->first();

        $this->assertCount(2, $result->twos);
        $this->assertEquals(2, $result->twos->cursor()->parameter('two.id'));
        $this->assertFalse($result->twos->cursor()->pointsToPreviousItems());
        $this->assertTrue($result->twos->cursor()->pointsToNextItems());

        $this->assertSame(3, $result->twos->first()->getKey());
        $this->assertSame(4, $result->twos->last()->getKey());

        $this->assertSame('foo', $result->twos->getCursorName());
    }

    public function testCursorPaginatesWithShorthandWithCursorArray()
    {
        $cursor = ['two.id' => 2];

        $result = Model1::withCursorPaged('twos', 2, $cursor, 'foo')->first();

        $this->assertCount(2, $result->twos);
        $this->assertEquals(2, $result->twos->cursor()->parameter('two.id'));
        $this->assertFalse($result->twos->cursor()->pointsToPreviousItems());
        $this->assertTrue($result->twos->cursor()->pointsToNextItems());

        $this->assertSame(3, $result->twos->first()->getKey());
        $this->assertSame(4, $result->twos->last()->getKey());

        $this->assertSame('foo', $result->twos->getCursorName());
    }

    public function testCursorPaginatesWithCallback()
    {
        $result = Model1::withCursorPaged('twos', function ($query, $pagination) {
            $query->where('id', '>', 1);

            /** @var \Illuminate\Database\Eloquent\Pagination $pagination */
            $pagination->perPage(2, 2, 'foo');
        })->first();

        $this->assertInstanceOf(CursorPaginator::class, $result->twos);
        $this->assertCount(2, $result->twos);

        $this->assertSame(2, $result->twos->first()->getKey());
        $this->assertSame(3, $result->twos->last()->getKey());

        $this->assertSame('foo', $result->twos->getCursorName());

        $this->assertSame(
            'http://localhost?foo=eyJ0d28uaWQiOjMsIl9wb2ludHNUb05leHRJdGVtcyI6dHJ1ZX0',
            $result->twos->nextPageUrl()
        );

        $this->assertNull($result->twos->previousPageUrl());
    }

    public function testCursorPaginatesMultipleRelations()
    {
        $result = Model1::withCursorPaged('twos', 'fours', 'allFours')->first();

        $this->assertInstanceOf(CursorPaginator::class, $result->twos);
        $this->assertCount(4, $result->twos);
        $this->assertInstanceOf(CursorPaginator::class, $result->fours);
        $this->assertCount(2, $result->fours);
        $this->assertInstanceOf(CursorPaginator::class, $result->allFours);
        $this->assertCount(3, $result->allFours);

        $this->assertSame('twos_page', $result->twos->getCursorName());
        $this->assertSame('fours_page', $result->fours->getCursorName());
        $this->assertSame('all_fours_page', $result->allFours->getCursorName());
    }

    public function testCursorPaginatesRelationsWithColumns()
    {
        $result = Model1::withCursorPaged('twos:id', 'fours', 'allFours:id')->first();

        $this->assertNull($result->twos->first()->one_id);
        $this->assertSame(1, $result->fours->first()->one_id);
        $this->assertNull($result->allFours->first()->one_id);
    }

    public function testExceptionCursorPaginatingRelationWithColumnsAndCallback()
    {
        $this->expectException(RelationNotFoundException::class);
        $this->expectExceptionMessage('Call to undefined relationship [twos:id] on model [Illuminate\Tests\Integration\Database\EloquentWithPaginationTest\Model1].');

        Model1::withCursorPaged('twos:id', function ($query, $pagination) {
            $query->select('one_id');

            $pagination->pageName('foo');
        })->first();
    }

    public function testCursorPaginatesSingleRelation()
    {
        $result = Model1::withCursorPaged('five.sixes')->skip(1)->first();

        $this->assertInstanceOf(CursorPaginator::class, $result->five);
        $this->assertInstanceOf(Collection::class, $result->five->first()->sixes);
    }

    /** General behavior */
    public function testQueryCallbackOnNestedRelation()
    {
        $result = Model1::withPaged('twos.threes', function ($query) {
            $query->where('id', '>', 3);
        })->first();

        $this->assertCount(4, $result->twos);
        $this->assertCount(2, $result->twos->get(2)->threes);
        $this->assertSame(4, $result->twos->get(2)->threes->first()->getKey());
        $this->assertSame(5, $result->twos->get(2)->threes->last()->getKey());
    }

    public function testPaginationGoneIfEagerLoadIsUnset()
    {
        $result = Model1::withPaged('twos')->without('twos')->first();

        $this->assertFalse($result->relationLoaded('twos'));

        $result = Model1::withPaged('twos')->withOnly('five.sixes')->first();

        $this->assertFalse($result->relationLoaded('twos'));
    }

    public function testPaginationNestedGoneIfEagerLoadIsUnset()
    {
        $result = Model1::withPaged('twos.threes')->without('twos')->first();

        $this->assertFalse($result->relationLoaded('twos'));

        $result = Model1::withPaged('twos.threes')->withOnly('twos')->first();

        $this->assertInstanceOf(Collection::class, $result->twos);
        $this->assertFalse($result->twos->first()->relationLoaded('threes'));
    }

    public function testPaginationGoneIfEagerLoadedKeyRewritten()
    {
        $result = Model1::withPaged('twos.threes', 'five.sixes')->with('twos.threes')->first();

        $this->assertInstanceOf(Collection::class, $result->twos);
        $this->assertInstanceOf(LengthAwarePaginator::class, $result->five);
    }
}

/**
 * @property-read \Illuminate\Support\Collection $twos
 * @property-read \Illuminate\Support\Collection $fours
 * @property-read \Illuminate\Support\Collection $allFours
 * @property-read \Illuminate\Tests\Integration\Database\EloquentWithPaginationTest\Model5|null $five
 */
class Model1 extends Model
{
    public $table = 'one';
    public $timestamps = false;
    protected $guarded = [];

    public function twos()
    {
        return $this->hasMany(Model2::class, 'one_id');
    }

    public function fours()
    {
        return $this->hasMany(Model4::class, 'one_id');
    }

    public function allFours()
    {
        return $this->fours()->withoutGlobalScopes();
    }

    public function five()
    {
        return $this->hasOne(Model5::class, 'one_id');
    }
}

class Model2 extends Model
{
    public $table = 'two';
    public $timestamps = false;
    protected $guarded = [];
    protected $withCount = ['threes'];

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('app', function ($builder) {
            $builder->latest();
        });
    }

    public function threes()
    {
        return $this->hasMany(Model3::class, 'two_id');
    }
}

class Model3 extends Model
{
    public $table = 'three';
    public $timestamps = false;
    protected $guarded = [];

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('app', function ($builder) {
            $builder->where('id', '>', 0);
        });
    }
}

class Model4 extends Model
{
    public $table = 'four';
    public $timestamps = false;
    protected $guarded = [];

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('app', function ($builder) {
            $builder->where('id', '>', 1);
        });
    }
}

class Model5 extends Model
{
    public $table = 'five';
    public $timestamps = false;
    protected $guarded = [];

    public function one()
    {
        return $this->belongsTo(Model1::class, 'one_id');
    }

    public function sixes()
    {
        return $this->hasMany(Model6::class, 'five_id');
    }
}

class Model6 extends Model
{
    public $table = 'six';
    public $timestamps = false;
    protected $guarded = [];
}
