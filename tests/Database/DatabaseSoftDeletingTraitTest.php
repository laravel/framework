<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Tests\App\Models\SoftDeletes\DatabaseSoftDeletingTraitStub;
use Mockery;
use PHPUnit\Framework\TestCase;

class DatabaseSoftDeletingTraitTest extends TestCase
{
    public function testDeleteSetsSoftDeletedColumn()
    {
        $model = Mockery::mock(DatabaseSoftDeletingTraitStub::class)->makePartial();
        $query = Mockery::mock(Builder::class);
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
