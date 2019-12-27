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

        $this->assertIsNumeric($item->toArray()['field_3']);

        $this->assertSame(
            strtotime('08:19:12'),
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
    public function get($value)
    {
        return strtotime($value);
    }

    public function set($value)
    {
        return is_numeric($value)
            ? date('H:i:s', strtotime($value))
            : $value;
    }
}

class StringCast implements Castable
{
    public function get($value)
    {
        return str_split($value);
    }

    public function set($value)
    {
        return is_array($value)
            ? implode('', $value)
            : $value;
    }
}

class NumberCast implements Castable
{
    public function get($value)
    {
        return $value / 100;
    }

    public function set($value)
    {
        return $value;
    }
}
