<?php

namespace Illuminate\Tests\Integration\Database\EloquentModelNumberCastingTest;

use BcMath\Number;
use Illuminate\Database\Eloquent\Casts\AsNumber;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Exceptions\MathException;
use Illuminate\Support\Facades\Schema;
use Illuminate\Tests\Integration\Database\DatabaseTestCase;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;

#[RequiresPhpExtension('bcmath')]
class EloquentModelNumberCastingTest extends DatabaseTestCase
{
    protected function afterRefreshingDatabase()
    {
        Schema::create('number_casting_orders', function (Blueprint $table) {
            $table->increments('id');
            $table->decimal('price', 10, 2)->nullable();
            $table->decimal('tax', 10, 2)->nullable();
            $table->decimal('rate', 10, 4)->nullable();
        });
    }

    public function testAttributesAreCastToNumbers()
    {
        $order = new NumberCastingOrder(['price' => '19.99']);

        $this->assertInstanceOf(Number::class, $order->price);
        $this->assertSame('19.99', (string) $order->price);
    }

    public function testArithmeticIsExact()
    {
        $order = new NumberCastingOrder(['price' => '0.10', 'tax' => '0.20']);

        $this->assertSame('0.30', (string) ($order->price + $order->tax));
        $this->assertSame('59.97', (string) ((new NumberCastingOrder(['price' => '19.99']))->price * 3));
    }

    public function testValuesAreRoundedToTheScaleHalfUp()
    {
        $order = new NumberCastingOrder;

        $order->price = '1.005';
        $this->assertSame('1.01', (string) $order->price);

        $order->price = new Number('2.344');
        $this->assertSame('2.34', (string) $order->price);

        $order->rate = '0.12345';
        $this->assertSame('0.1235', (string) $order->rate);
    }

    public function testIntegersStringsFloatsAndNumbersAreAccepted()
    {
        $order = new NumberCastingOrder;

        $order->price = 10;
        $this->assertSame('10.00', (string) $order->price);

        $order->price = '10.5';
        $this->assertSame('10.50', (string) $order->price);

        $order->price = 10.25;
        $this->assertSame('10.25', (string) $order->price);

        $order->price = new Number('10.75');
        $this->assertSame('10.75', (string) $order->price);
    }

    public function testValuesAreStoredAsStringsWithTheScale()
    {
        $order = new NumberCastingOrder;

        $order->price = new Number('7.5');

        $this->assertSame('7.50', $order->getAttributes()['price']);
    }

    public function testNullIsPreserved()
    {
        $order = new NumberCastingOrder(['price' => null]);

        $this->assertNull($order->price);
        $this->assertNull($order->getAttributes()['price']);
    }

    public function testInvalidValuesThrowAMathException()
    {
        $this->expectException(MathException::class);

        $order = new NumberCastingOrder;

        $order->price = 'foo';
    }

    public function testCastWithoutScaleKeepsTheValueAsGiven()
    {
        $order = new NumberCastingOrderWithoutScale(['price' => '12.3456']);

        $this->assertSame('12.3456', (string) $order->price);
    }

    public function testValuesRoundTripThroughTheDatabase()
    {
        $order = NumberCastingOrder::create(['price' => '19.90', 'tax' => '1.50']);

        $fresh = $order->fresh();

        $this->assertInstanceOf(Number::class, $fresh->price);
        $this->assertSame('19.90', (string) $fresh->price);
        $this->assertSame('21.40', (string) ($fresh->price + $fresh->tax));
    }

    public function testEquivalentValuesDoNotMakeTheModelDirty()
    {
        $order = NumberCastingOrder::create(['price' => '19.90'])->fresh();

        $order->price = new Number('19.9');
        $this->assertFalse($order->isDirty('price'));

        $order->price = '19.91';
        $this->assertTrue($order->isDirty('price'));
    }

    public function testNumbersAreSerializedAsStrings()
    {
        $order = new NumberCastingOrder(['price' => '19.9', 'tax' => null]);

        $this->assertSame('19.90', $order->toArray()['price']);
        $this->assertNull($order->toArray()['tax']);
        $this->assertSame('{"price":"19.90","tax":null}', $order->toJson());
    }

    public function testScaleHelper()
    {
        $this->assertSame(AsNumber::class.':2', AsNumber::of(2));
    }
}

class NumberCastingOrder extends Model
{
    public $table = 'number_casting_orders';

    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'price' => AsNumber::of(2),
            'tax' => AsNumber::of(2),
            'rate' => AsNumber::of(4),
        ];
    }
}

class NumberCastingOrderWithoutScale extends Model
{
    public $table = 'number_casting_orders';

    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'price' => AsNumber::class,
        ];
    }
}
