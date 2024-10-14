<?php

namespace Illuminate\Tests\Integration\Database\EloquentModelDateCastingTest;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Tests\Integration\Database\DatabaseTestCase;

class EloquentModelImmutableDateCastingTest extends DatabaseTestCase
{
    protected function afterRefreshingDatabase()
    {
        Schema::create('test_model_immutable', function (Blueprint $table) {
            $table->increments('id');
            $table->date('date_field')->nullable();
            $table->datetime('datetime_field')->nullable();
        });
    }

    public function testDatesAreImmutableCastable()
    {
        $model = TestModelImmutable::create([
            'date_field' => '2019-10-01',
            'datetime_field' => '2019-10-01 10:15:20',
        ]);

        $this->assertSame('2019-10-01T00:00:00.000000Z', $model->toArray()['date_field']);
        $this->assertSame('2019-10-01T10:15:20.000000Z', $model->toArray()['datetime_field']);
        $this->assertInstanceOf(CarbonImmutable::class, $model->date_field);
        $this->assertInstanceOf(CarbonImmutable::class, $model->datetime_field);
    }

    public function testDatesAreImmutableAndCustomCastable()
    {
        $model = TestModelCustomImmutable::create([
            'date_field' => '2019-10-01',
            'datetime_field' => '2019-10-01 10:15:20',
        ]);

        $this->assertSame('2019-10', $model->toArray()['date_field']);
        $this->assertSame('2019-10 10:15', $model->toArray()['datetime_field']);
        $this->assertInstanceOf(CarbonImmutable::class, $model->date_field);
        $this->assertInstanceOf(CarbonImmutable::class, $model->datetime_field);
    }
}

class TestModelImmutable extends Model
{
    public $table = 'test_model_immutable';
    public $timestamps = false;
    protected $guarded = [];

    public $casts = [
        'date_field' => 'immutable_date',
        'datetime_field' => 'immutable_datetime',
    ];
}

class TestModelCustomImmutable extends Model
{
    public $table = 'test_model_immutable';
    public $timestamps = false;
    protected $guarded = [];

    public $casts = [
        'date_field' => 'immutable_date:Y-m',
        'datetime_field' => 'immutable_datetime:Y-m H:i',
    ];
}
