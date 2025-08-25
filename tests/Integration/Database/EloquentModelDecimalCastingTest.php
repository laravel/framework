<?php

namespace Illuminate\Tests\Integration\Database\EloquentModelDecimalCastingTest;

use Brick\Math\Exception\NumberFormatException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Exceptions\MathException;
use Illuminate\Support\Facades\Schema;
use Illuminate\Tests\Integration\Database\DatabaseTestCase;

class EloquentModelDecimalCastingTest extends DatabaseTestCase
{
    protected function afterRefreshingDatabase()
    {
        Schema::create('test_model1', function (Blueprint $table) {
            $table->increments('id');
            $table->decimal('decimal_field_2', 8, 2)->nullable();
            $table->decimal('decimal_field_4', 8, 4)->nullable();
        });
    }

    public function testItHandlesExponent()
    {
        $model = new class extends Model
        {
            public $timestamps = false;

            protected $casts = [
                'amount' => 'decimal:20',
            ];
        };

        $model->amount = 0.123456789e3;
        $this->assertSame('123.45678900000000000000', $model->amount);

        $model->amount = '0.123456789e3';
        $this->assertSame('123.45678900000000000000', $model->amount);
    }

    public function testItHandlesIntegersWithUnderscores()
    {
        $model = new class extends Model
        {
            public $timestamps = false;

            protected $casts = [
                'amount' => 'decimal:2',
            ];
        };

        $model->amount = 1_234.5;
        $this->assertSame('1234.50', $model->amount);
    }

    public function testItWrapsThrownExceptions()
    {
        $model = new class extends Model
        {
            public $timestamps = false;

            protected $casts = [
                'amount' => 'decimal:20',
            ];
        };
        $model->amount = 'foo';

        try {
            $model->amount;
            $this->fail();
        } catch (MathException $e) {
            $this->assertSame('Unable to cast value to a decimal.', $e->getMessage());
            $this->assertInstanceOf(NumberFormatException::class, $e->getPrevious());
            $this->assertSame('The given value "foo" does not represent a valid number.', $e->getPrevious()->getMessage());
        }
    }

    public function testItHandlesMissingIntegers()
    {
        $model = new class extends Model
        {
            public $timestamps = false;

            protected $casts = [
                'amount' => 'decimal:2',
            ];
        };

        $model->amount = .8;
        $this->assertSame('0.80', $model->amount);

        $model->amount = '.8';
        $this->assertSame('0.80', $model->amount);
    }

    public function testItHandlesLargeNumbers()
    {
        $model = new class extends Model
        {
            public $timestamps = false;

            protected $casts = [
                'amount' => 'decimal:20',
            ];
        };

        $model->amount = '0.89898989898989898989';
        $this->assertSame('0.89898989898989898989', $model->amount);

        $model->amount = '89898989898989898989';
        $this->assertSame('89898989898989898989.00000000000000000000', $model->amount);
    }

    public function testItRounds()
    {
        $model = new class extends Model
        {
            public $timestamps = false;

            protected $casts = [
                'amount' => 'decimal:2',
            ];
        };

        $model->amount = '0.8989898989';
        $this->assertSame('0.90', $model->amount);
    }

    public function testItTrimsLongValues()
    {
        $model = new class extends Model
        {
            public $timestamps = false;

            protected $casts = [
                'amount' => 'decimal:20',
            ];
        };

        $model->amount = '0.89898989898989898989898989898989898989898989';
        $this->assertSame('0.89898989898989898990', $model->amount);
    }

    public function testItDoesntRoundNumbers()
    {
        $model = new class extends Model
        {
            public $timestamps = false;

            protected $casts = [
                'amount' => 'decimal:1',
            ];
        };

        $model->amount = '0.99';
        $this->assertSame('1.0', $model->amount);
    }

    public function testDecimalsAreCastable()
    {
        $user = TestModel1::create([
            'decimal_field_2' => '12',
            'decimal_field_4' => '1234',
        ]);

        $this->assertSame('12.00', $user->toArray()['decimal_field_2']);
        $this->assertSame('1234.0000', $user->toArray()['decimal_field_4']);

        $user->decimal_field_2 = 12;
        $user->decimal_field_4 = '1234';

        $this->assertSame('12.00', $user->toArray()['decimal_field_2']);
        $this->assertSame('1234.0000', $user->toArray()['decimal_field_4']);

        $this->assertFalse($user->isDirty());

        $user->decimal_field_4 = '1234.1234';
        $this->assertTrue($user->isDirty());
    }

    public function testRoundingDirection()
    {
        $model = new class extends Model
        {
            protected $casts = [
                'amount' => 'decimal:2',
            ];
        };

        $model->amount = '0.999';
        $this->assertSame('1.00', $model->amount);

        $model->amount = '-0.999';
        $this->assertSame('-1.00', $model->amount);

        $model->amount = '0.554';
        $this->assertSame('0.55', $model->amount);

        $model->amount = '-0.554';
        $this->assertSame('-0.55', $model->amount);

        $model->amount = '0.555';
        $this->assertSame('0.56', $model->amount);

        $model->amount = '-0.555';
        $this->assertSame('-0.56', $model->amount);

        $model->amount = '0.005';
        $this->assertSame('0.01', $model->amount);

        $model->amount = '-0.005';
        $this->assertSame('-0.01', $model->amount);

        $model->amount = '0.8989898989';
        $this->assertSame('0.90', $model->amount);

        $model->amount = '-0.8989898989';
        $this->assertSame('-0.90', $model->amount);
    }
}

class TestModel1 extends Model
{
    public $table = 'test_model1';
    public $timestamps = false;
    protected $guarded = [];

    public $casts = [
        'decimal_field_2' => 'decimal:2',
        'decimal_field_4' => 'decimal:4',
    ];
}
