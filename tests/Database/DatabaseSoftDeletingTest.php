<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;

class DatabaseSoftDeletingTest extends TestCase
{
    public function testDeletedAtIsAddedToCastsAsDefaultType()
    {
        $model = new SoftDeletingModel;

        $this->assertArrayHasKey('deleted_at', $model->getCasts());
        $this->assertSame('datetime', $model->getCasts()['deleted_at']);
    }

    public function testDeletedAtIsCastToCarbonInstance()
    {
        $expected = Carbon::createFromFormat('Y-m-d H:i:s', '2018-12-29 13:59:39');
        $model = new SoftDeletingModel(['deleted_at' => $expected->format('Y-m-d H:i:s')]);

        $this->assertInstanceOf(Carbon::class, $model->deleted_at);
        $this->assertTrue($expected->eq($model->deleted_at));
    }

    public function testExistingCastOverridesAddedDateCast()
    {
        $model = new class(['deleted_at' => '2018-12-29 13:59:39']) extends SoftDeletingModel
        {
            protected $casts = ['deleted_at' => 'bool'];
        };

        $this->assertTrue($model->deleted_at);
    }

    public function testExistingMutatorOverridesAddedDateCast()
    {
        $model = new class(['deleted_at' => '2018-12-29 13:59:39']) extends SoftDeletingModel
        {
            protected function getDeletedAtAttribute()
            {
                return 'expected';
            }
        };

        $this->assertSame('expected', $model->deleted_at);
    }

    public function testCastingToStringOverridesAutomaticDateCastingToRetainPreviousBehaviour()
    {
        $model = new class(['deleted_at' => '2018-12-29 13:59:39']) extends SoftDeletingModel
        {
            protected $casts = ['deleted_at' => 'string'];
        };

        $this->assertSame('2018-12-29 13:59:39', $model->deleted_at);
    }
}

class SoftDeletingModel extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected $dateFormat = 'Y-m-d H:i:s';
}
