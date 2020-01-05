<?php

namespace Illuminate\Tests\Integration\Database;

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
        $model->encrypted = 'taylor';

        $this->assertEquals('taylor', $model->encrypted);
        $this->assertEquals('rolyat', $model->getAttributes()['encrypted']);
        $this->assertEquals('rolyat', $model->toArray()['encrypted']);

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
        'encrypted' => EncryptCaster::class,
    ];
}

class EncryptCaster
{
    public static function fromModelAttributes($model, $key, $attributes)
    {
        return strrev($attributes[$key]);
    }

    public static function toModelAttributes($model, $key, $value, $attributes)
    {
        return [$key => strrev($value)];
    }
}

class AddressCaster
{
    public static function fromModelAttributes($model, $key, $value, $attributes)
    {
        return new Address($attributes['address_line_one'], $attributes['address_line_two']);
    }

    public static function toModelAttributes($model, $key, $value, $attributes)
    {
        return ['address_line_one' => $value->lineOne, 'address_line_two' => $value->lineTwo];
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
