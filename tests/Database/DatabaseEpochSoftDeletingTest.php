<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Eloquent\Casts\EpochCast;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\EpochSoftDeletes;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;

class DatabaseEpochSoftDeletingTest extends TestCase
{
    public function testDeletedAtIsAddedToCastsAsDefaultType()
    {
        $model = new EpochSoftDeletingModel;

        $this->assertArrayHasKey('deleted_at', $model->getCasts());
        $this->assertSame(EpochCast::class, $model->getCasts()['deleted_at']);
    }

    public function testDeletedAtIsCastToCarbonInstance()
    {
        $expected = Carbon::createFromFormat('Y-m-d H:i:s', '2018-12-29 13:59:39');
        $model = new EpochSoftDeletingModel(['deleted_at' => $expected->timestamp]);

        $this->assertInstanceOf(Carbon::class, $model->deleted_at);
        $this->assertTrue($expected->eq($model->deleted_at));
    }

    public function testExistingCastOverridesAddedDateCast()
    {
        $model = new class(['deleted_at' => Carbon::now()->timestamp]) extends EpochSoftDeletingModel
        {
            protected $casts = ['deleted_at' => 'bool'];
        };

        $this->assertTrue($model->deleted_at);
    }

    public function testExistingMutatorOverridesAddedDateCast()
    {
        $model = new class(['deleted_at' => Carbon::now()->timestamp]) extends EpochSoftDeletingModel
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
        $model = new class(['deleted_at' => Carbon::now()->timestamp]) extends EpochSoftDeletingModel
        {
            protected $casts = ['deleted_at' => 'string'];
        };

        $this->assertSame((string) Carbon::now()->timestamp, $model->deleted_at);
    }
}

class EpochSoftDeletingModel extends Model
{
    use EpochSoftDeletes;

    protected $guarded = [];

    protected $dateFormat = 'Y-m-d H:i:s';
}
