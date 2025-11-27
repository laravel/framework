<?php

namespace Illuminate\Tests\Integration\Database\EloquentModelDateCastingTest;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Tests\Integration\Database\DatabaseTestCase;

class EloquentModelDateCastingTest extends DatabaseTestCase
{
    /** {@inheritdoc} */
    protected function afterRefreshingDatabase()
    {
        Schema::create('test_model1', function (Blueprint $table) {
            $table->increments('id');
            $table->date('date_field')->nullable();
            $table->datetime('datetime_field')->nullable();
            $table->date('immutable_date_field')->nullable();
            $table->datetime('immutable_datetime_field')->nullable();
        });

        Schema::create('test_model2', function (Blueprint $table) {
            $table->increments('id');
            $table->date('date_field')->nullable();
            $table->datetime('datetime_field')->nullable();
            $table->date('immutable_date_field')->nullable();
            $table->datetime('immutable_datetime_field')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function testDatesAreCustomCastable()
    {
        $user = TestModel1::create([
            'date_field' => '2019-10-01',
            'datetime_field' => '2019-10-01 10:15:20',
        ]);

        $this->assertSame('2019-10', $user->toArray()['date_field']);
        $this->assertSame('2019-10 10:15', $user->toArray()['datetime_field']);
        $this->assertInstanceOf(Carbon::class, $user->date_field);
        $this->assertInstanceOf(Carbon::class, $user->datetime_field);
    }

    public function testDatesFormattedAttributeBindings()
    {
        $bindings = [];

        $this->app->make('db')->listen(static function ($query) use (&$bindings) {
            $bindings = $query->bindings;
        });

        TestModel1::create([
            'date_field' => '2019-10-01',
            'datetime_field' => '2019-10-01 10:15:20',
            'immutable_date_field' => '2019-10-01',
            'immutable_datetime_field' => '2019-10-01 10:15',
        ]);

        $this->assertSame(['2019-10-01', '2019-10-01 10:15:20', '2019-10-01', '2019-10-01 10:15'], $bindings);
    }

    public function testDatesFormattedArrayAndJson()
    {
        $user = TestModel1::create([
            'date_field' => '2019-10-01',
            'datetime_field' => '2019-10-01 10:15:20',
            'immutable_date_field' => '2019-10-01',
            'immutable_datetime_field' => '2019-10-01 10:15',
        ]);

        $expected = [
            'date_field' => '2019-10',
            'datetime_field' => '2019-10 10:15',
            'immutable_date_field' => '2019-10',
            'immutable_datetime_field' => '2019-10 10:15',
            'id' => 1,
        ];

        $this->assertSame($expected, $user->toArray());
        $this->assertSame(json_encode($expected), $user->toJson());
    }

    public function testCustomDateCastsAreComparedAsDatesForCarbonInstances()
    {
        $user = TestModel1::create([
            'date_field' => '2019-10-01',
            'datetime_field' => '2019-10-01 10:15:20',
            'immutable_date_field' => '2019-10-01',
            'immutable_datetime_field' => '2019-10-01 10:15:20',
        ]);

        $user->date_field = new Carbon('2019-10-01');
        $user->datetime_field = new Carbon('2019-10-01 10:15:20');
        $user->immutable_date_field = new CarbonImmutable('2019-10-01');
        $user->immutable_datetime_field = new CarbonImmutable('2019-10-01 10:15:20');

        $this->assertArrayNotHasKey('date_field', $user->getDirty());
        $this->assertArrayNotHasKey('datetime_field', $user->getDirty());
        $this->assertArrayNotHasKey('immutable_date_field', $user->getDirty());
        $this->assertArrayNotHasKey('immutable_datetime_field', $user->getDirty());
    }

    public function testCustomDateCastsAreComparedAsDatesForStringValues()
    {
        $user = TestModel1::create([
            'date_field' => '2019-10-01',
            'datetime_field' => '2019-10-01 10:15:20',
            'immutable_date_field' => '2019-10-01',
            'immutable_datetime_field' => '2019-10-01 10:15:20',
        ]);

        $user->date_field = '2019-10-01';
        $user->datetime_field = '2019-10-01 10:15:20';
        $user->immutable_date_field = '2019-10-01';
        $user->immutable_datetime_field = '2019-10-01 10:15:20';

        $this->assertArrayNotHasKey('date_field', $user->getDirty());
        $this->assertArrayNotHasKey('datetime_field', $user->getDirty());
        $this->assertArrayNotHasKey('immutable_date_field', $user->getDirty());
        $this->assertArrayNotHasKey('immutable_datetime_field', $user->getDirty());
    }

    public function testDatesCanBeSerializedToArray()
    {
        $this->freezeSecond(function ($now) {
            $user = TestModel2::create([
                'date_field' => '2019-10-01',
                'datetime_field' => '2019-10-01 10:15:20',
                'immutable_date_field' => '2019-10-01',
                'immutable_datetime_field' => '2019-10-01 10:15:20',
            ]);

            $this->assertSame(['created_at', null], $user->getDates());

            $user->refresh();

            $this->assertSame([
                'id' => $user->getKey(),
                'date_field' => '2019-10',
                'datetime_field' => '2019-10 10:15',
                'immutable_date_field' => '2019-10',
                'immutable_datetime_field' => '2019-10 10:15',
                'created_at' => $now->toISOString(),
            ], $user->attributesToArray());
        });
    }
}

class TestModel1 extends Model
{
    public $table = 'test_model1';
    public $timestamps = false;
    protected $guarded = [];

    public $casts = [
        'date_field' => 'date:Y-m',
        'datetime_field' => 'datetime:Y-m H:i',
        'immutable_date_field' => 'date:Y-m',
        'immutable_datetime_field' => 'datetime:Y-m H:i',
    ];
}

class TestModel2 extends Model
{
    public $table = 'test_model2';
    const UPDATED_AT = null;
    protected $guarded = [];

    public $casts = [
        'date_field' => 'date:Y-m',
        'datetime_field' => 'datetime:Y-m H:i',
        'immutable_date_field' => 'date:Y-m',
        'immutable_datetime_field' => 'datetime:Y-m H:i',
    ];
}
