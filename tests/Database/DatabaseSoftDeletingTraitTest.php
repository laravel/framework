<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Mockery;
use PHPUnit\Framework\TestCase;
use stdClass;

class DatabaseSoftDeletingTraitTest extends TestCase
{
    public function testDeleteSetsSoftDeletedColumn()
    {
        $model = Mockery::mock(DatabaseSoftDeletingTraitStub::class)->makePartial();
        $query = Mockery::mock(stdClass::class);
        $model->expects('newModelQuery')->andReturn($query);
        $query->expects('where')->with('id', '=', 1)->andReturn($query);
        $query->expects('update')->with([
            'deleted_at' => 'date-time',
            'updated_at' => 'date-time',
        ]);
        $model->expects('syncOriginalAttributes')->with([
            'deleted_at',
            'updated_at',
        ]);
        $model->expects('usesTimestamps')->andReturn(true);
        $model->delete();

        $this->assertInstanceOf(Carbon::class, $model->deleted_at);
    }

    public function testRestore()
    {
        $model = Mockery::mock(DatabaseSoftDeletingTraitStub::class)->makePartial();
        $model->expects('fireModelEvent')->with('restoring')->andReturn(true);
        $model->expects('save');

        $model->restore();

        $this->assertNull($model->deleted_at);
    }

    public function testRestoreCancel()
    {
        $model = Mockery::mock(DatabaseSoftDeletingTraitStub::class)->makePartial();
        $model->expects('fireModelEvent')->with('restoring')->andReturn(false);
        $model->shouldReceive('save')->never();

        $this->assertFalse($model->restore());
    }
}

class DatabaseSoftDeletingTraitStub
{
    use SoftDeletes;

    public $deleted_at;
    public $updated_at;
    public $timestamps = true;
    public $exists = false;

    public function newQuery()
    {
        //
    }

    public function getKey()
    {
        return 1;
    }

    public function getKeyName()
    {
        return 'id';
    }

    public function save()
    {
        //
    }

    public function delete()
    {
        return $this->performDeleteOnModel();
    }

    public function fireModelEvent()
    {
        //
    }

    public function freshTimestamp()
    {
        return Carbon::now();
    }

    public function fromDateTime()
    {
        return 'date-time';
    }

    public function getUpdatedAtColumn()
    {
        return defined('static::UPDATED_AT') ? static::UPDATED_AT : 'updated_at';
    }

    public function setKeysForSaveQuery($query)
    {
        $query->where($this->getKeyName(), '=', $this->getKeyForSaveQuery());

        return $query;
    }

    protected function getKeyForSaveQuery()
    {
        return 1;
    }
}
