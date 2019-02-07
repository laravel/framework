<?php

namespace Illuminate\Tests\Integration\Database\EloquentModelDecimalCastingTest;

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Tests\Integration\Database\DatabaseTestCase;

/**
 * @group integration
 */
class EloquentModelDecimalCastingTest extends DatabaseTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Schema::create('test_model1', function ($table) {
            $table->increments('id');
            $table->decimal('decimal_field_2', 8, 2)->nullable();
            $table->decimal('decimal_field_4', 8, 4)->nullable();
        });
    }

    public function test_decimals_are_castable()
    {
        $user = TestModel1::create([
            'decimal_field_2' => '12',
            'decimal_field_4' => '1234',
        ]);

        $this->assertEquals('12.00', $user->toArray()['decimal_field_2']);
        $this->assertEquals('1234.0000', $user->toArray()['decimal_field_4']);

        $user->decimal_field_2 = 12;
        $user->decimal_field_4 = '1234';

        $this->assertEquals('12.00', $user->toArray()['decimal_field_2']);
        $this->assertEquals('1234.0000', $user->toArray()['decimal_field_4']);

        $this->assertFalse($user->isDirty());

        $user->decimal_field_4 = '1234.1234';
        $this->assertTrue($user->isDirty());
    }
}

class TestModel1 extends Model
{
    public $table = 'test_model1';
    public $timestamps = false;
    protected $guarded = ['id'];

    public $casts = [
        'decimal_field_2' => 'decimal:2',
        'decimal_field_4' => 'decimal:4',
    ];
}
