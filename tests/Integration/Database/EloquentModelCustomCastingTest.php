<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Contracts\Database\Eloquent\Castable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class EloquentModelCustomCastingTest extends DatabaseTestCase
{
    public function testFoo()
    {
        $item = TestModel::create([
            'field_1' => 'foobar',
            'field_2' => 20,
            'field_3' => '08:19:12',
        ]);

        $this->assertSame(['f', 'o', 'o', 'b', 'a', 'r'], $item->toArray()['field_1']);

        $this->assertSame(0.2, $item->toArray()['field_2']);

        $this->assertIsArray($item->toArray()['field_3']);

        $this->assertSame(
            [
                'year'          => false,
                'month'         => false,
                'day'           => false,
                'hour'          => 8,
                'minute'        => 19,
                'second'        => 12,
                'fraction'      => 0.0,
                'warning_count' => 0,
                'warnings'      => [],
                'error_count'   => 0,
                'errors'        => [],
                'is_localtime'  => false,
            ],
            $item->toArray()['field_3']
        );
    }

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('test_model1', function (Blueprint $table) {
            $table->increments('id');
            $table->string('field_1')->nullable();
            $table->integer('field_2')->nullable();
            $table->time('field_3')->nullable();
        });
    }
}

class TestModel extends Model
{
    public $table = 'test_model1';

    public $timestamps = false;

public $casts = [
    'field_1' => StringCast::class,
    'field_2' => NumberCast::class,
    'field_3' => TimeCast::class,
];

    protected $guarded = ['id'];
}

class TimeCast implements Castable
{
    public function handle($value = null)
    {
        return date_parse($value);
    }
}

class StringCast implements Castable
{
    public function handle($value = null)
    {
        return mb_str_split($value);
    }
}

class NumberCast implements Castable
{
    public function handle($value = null)
    {
        return $value / 100;
    }
}
