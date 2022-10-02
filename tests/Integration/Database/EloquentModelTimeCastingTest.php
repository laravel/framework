<?php

namespace Illuminate\Tests\Integration\Database\EloquentModelTimeCastingTest;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Tests\Integration\Database\DatabaseTestCase;

class EloquentModelTimeCastingTest extends DatabaseTestCase
{
    protected function defineDatabaseMigrationsAfterDatabaseRefreshed()
    {
        Schema::create('test_model1', function (Blueprint $table) {
            $table->increments('id');
            $table->time('start_at');
            $table->time('end_at');
        });
    }

    public function testTimesAreCustomCastable()
    {
        $user = TestModel1::create([
            'start_at' => '10:30',
            'end_at' => '10:15:20',
        ]);

        $this->assertSame('2019-10', $user->toArray()['start_at']);
        $this->assertSame('2019-10 10:15', $user->toArray()['end_at']);
        $this->assertInstanceOf(Carbon::class, $user->start_at);
        $this->assertInstanceOf(Carbon::class, $user->end_at);
    }

    public function testTimesFormattedAttributeBindings()
    {
        $bindings = [];

        $this->app->make('db')->listen(static function ($query) use (&$bindings) {
            $bindings = $query->bindings;
        });

        TestModel1::create([
            'start_at' => '10:30',
            'end_at' => '10:15:20',
        ]);

        $this->assertSame(['10:30', '10:15:20'], $bindings);
    }

    public function testTimesFormattedArrayAndJson()
    {
        $user = TestModel1::create([
            'start_at' => '10:30',
            'end_at' => '10:15:20',
        ]);

        $expected = [
            'id' => 1,
            'start_at' => '2019-10',
            'end_at' => '2019-10 10:15',
        ];

        $this->assertSame($expected, $user->toArray());
        $this->assertSame(json_encode($expected), $user->toJson());
    }

    public function testCustomDateCastsAreComparedAsTimesForCarbonInstances()
    {
        $user = TestModel2::create([
            'start_at' => '10:30',
            'end_at' => '10:15:20',
        ]);

        $user->start_at = new Carbon('10:30');
        $user->end_at = new Carbon('10:15:20');

        $this->assertArrayNotHasKey('start_at', $user->getDirty());
        $this->assertArrayNotHasKey('end_at', $user->getDirty());
    }

    public function testCustomDateCastsAreComparedAsTimesForStringValues()
    {
        $user = TestModel1::create([
            'start_at' => '10:30',
            'end_at' => '10:15:20',
        ]);

        $user->start_at = '10:30';
        $user->end_at = '10:15:20';

        $this->assertArrayNotHasKey('start_at', $user->getDirty());
        $this->assertArrayNotHasKey('end_at', $user->getDirty());
    }
}

class TestModel1 extends Model
{
    public $table = 'test_model1';
    public $timestamps = false;
    protected $guarded = [];

    public $casts = [
        'start_at' => 'time:H:i',
        'end_at' => 'time',
    ];
}
