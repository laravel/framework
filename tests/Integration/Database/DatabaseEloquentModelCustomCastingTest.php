<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * @group integration
 */
class DatabaseEloquentModelCustomCastingTest extends DatabaseTestCase
{
    public function testBasicCustomCasting()
    {
        $model = new TestEloquentModelWithCustomCast;
        $model->reversed = 'taylor';

        $this->assertEquals('taylor', $model->reversed);
        $this->assertEquals('rolyat', $model->getAttributes()['reversed']);
        $this->assertEquals('rolyat', $model->toArray()['reversed']);

        $model->setRawAttributes([
            'address_line_one' => '110 Kingsbrook St.',
            'address_line_two' => 'My House',
        ]);

        $this->assertEquals('110 Kingsbrook St.', $model->address->lineOne);
        $this->assertEquals('My House', $model->address->lineTwo);

        $this->assertEquals('110 Kingsbrook St.', $model->toArray()['address_line_one']);
        $this->assertEquals('My House', $model->toArray()['address_line_two']);

        $model->address->lineOne = '117 Spencer St.';

        $this->assertFalse(isset($model->toArray()['address']));
        $this->assertEquals('117 Spencer St.', $model->toArray()['address_line_one']);
        $this->assertEquals('My House', $model->toArray()['address_line_two']);

        $this->assertEquals('117 Spencer St.', json_decode($model->toJson(), true)['address_line_one']);
        $this->assertEquals('My House', json_decode($model->toJson(), true)['address_line_two']);

        $model->address = null;

        $this->assertNull($model->toArray()['address_line_one']);
        $this->assertNull($model->toArray()['address_line_two']);

        $model->options = ['foo' => 'bar'];
        $this->assertEquals(['foo' => 'bar'], $model->options);
        $this->assertEquals(json_encode(['foo' => 'bar']), $model->getAttributes()['options']);
    }

    public function testOneWayCasting()
    {
        $model = new TestEloquentModelWithCustomCast;

        $model->password = 'secret';
        dd($model->password);
    }
}

class TestEloquentModelWithCustomCast extends Model
{
    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'address' => AddressCaster::class,
        'password' => HashCaster::class,
        'other_password' => HashCaster::class.':md5',
        'reversed' => ReverseCaster::class,
        'options' => JsonCaster::class,
    ];
}

class HashCaster implements CastsAttributes
{
    public function __construct($algorithm = 'sha256')
    {
        $this->algorithm = $algorithm;
    }

    public function get($model, $key, $value, $attributes)
    {
        return $value;
    }

    public function set($model, $key, $value, $attributes)
    {
        dump('here');
        return [$key => hash($this->algorithm, $value)];
    }
}

class ReverseCaster implements CastsAttributes
{
    public function get($model, $key, $value, $attributes)
    {
        return strrev($value);
    }

    public function set($model, $key, $value, $attributes)
    {
        return [$key => strrev($value)];
    }
}

class AddressCaster implements CastsAttributes
{
    public function get($model, $key, $value, $attributes)
    {
        return new Address($attributes['address_line_one'], $attributes['address_line_two']);
    }

    public function set($model, $key, $value, $attributes)
    {
        return ['address_line_one' => $value->lineOne, 'address_line_two' => $value->lineTwo];
    }
}

class JsonCaster implements CastsAttributes
{
    public function get($model, $key, $value, $attributes)
    {
        return json_decode($value, true);
    }

    public function set($model, $key, $value, $attributes)
    {
        return [$key => json_encode($value)];
    }
}

class Address
{
    public $lineOne;
    public $lineTwo;

    public function __construct($lineOne, $lineTwo)
    {
        $this->lineOne = $lineOne;
        $this->lineTwo = $lineTwo;
    }
}
