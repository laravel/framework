<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Database\Eloquent\CastsInboundAttributes;
use Illuminate\Database\Eloquent\Model;

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

        $unserializedModel = unserialize(serialize($model));

        $this->assertEquals('taylor', $unserializedModel->reversed);
        $this->assertEquals('rolyat', $unserializedModel->getAttributes()['reversed']);
        $this->assertEquals('rolyat', $unserializedModel->toArray()['reversed']);

        $model = new TestEloquentModelWithCustomCast;

        $model->address = $address = new Address('110 Kingsbrook St.', 'My Childhood House');
        $address->lineOne = '117 Spencer St.';
        $this->assertEquals('117 Spencer St.', $model->getAttributes()['address_line_one']);

        $model = new TestEloquentModelWithCustomCast;

        $model->setRawAttributes([
            'address_line_one' => '110 Kingsbrook St.',
            'address_line_two' => 'My Childhood House',
        ]);

        $this->assertEquals('110 Kingsbrook St.', $model->address->lineOne);
        $this->assertEquals('My Childhood House', $model->address->lineTwo);

        $this->assertEquals('110 Kingsbrook St.', $model->toArray()['address_line_one']);
        $this->assertEquals('My Childhood House', $model->toArray()['address_line_two']);

        $model->address->lineOne = '117 Spencer St.';

        $this->assertFalse(isset($model->toArray()['address']));
        $this->assertEquals('117 Spencer St.', $model->toArray()['address_line_one']);
        $this->assertEquals('My Childhood House', $model->toArray()['address_line_two']);

        $this->assertEquals('117 Spencer St.', json_decode($model->toJson(), true)['address_line_one']);
        $this->assertEquals('My Childhood House', json_decode($model->toJson(), true)['address_line_two']);

        $model->address = null;

        $this->assertNull($model->toArray()['address_line_one']);
        $this->assertNull($model->toArray()['address_line_two']);

        $model->options = ['foo' => 'bar'];
        $this->assertEquals(['foo' => 'bar'], $model->options);
        $this->assertEquals(['foo' => 'bar'], $model->options);
        $model->options = ['foo' => 'bar'];
        $model->options = ['foo' => 'bar'];
        $this->assertEquals(['foo' => 'bar'], $model->options);
        $this->assertEquals(['foo' => 'bar'], $model->options);

        $this->assertEquals(json_encode(['foo' => 'bar']), $model->getAttributes()['options']);

        $model = new TestEloquentModelWithCustomCast(['options' => []]);
        $model->syncOriginal();
        $model->options = ['foo' => 'bar'];
        $this->assertTrue($model->isDirty('options'));
    }

    public function testOneWayCasting()
    {
        // CastsInboundAttributes is used for casting that is unidirectional... only use case I can think of is one-way hashing...
        $model = new TestEloquentModelWithCustomCast;

        $model->password = 'secret';

        $this->assertEquals(hash('sha256', 'secret'), $model->password);
        $this->assertEquals(hash('sha256', 'secret'), $model->getAttributes()['password']);
        $this->assertEquals(hash('sha256', 'secret'), $model->getAttributes()['password']);
        $this->assertEquals(hash('sha256', 'secret'), $model->password);

        $model->password = 'secret2';

        $this->assertEquals(hash('sha256', 'secret2'), $model->password);
        $this->assertEquals(hash('sha256', 'secret2'), $model->getAttributes()['password']);
        $this->assertEquals(hash('sha256', 'secret2'), $model->getAttributes()['password']);
        $this->assertEquals(hash('sha256', 'secret2'), $model->password);
    }

    public function testSettingRawAttributesClearsTheCastCache()
    {
        $model = new TestEloquentModelWithCustomCast;

        $model->setRawAttributes([
            'address_line_one' => '110 Kingsbrook St.',
            'address_line_two' => 'My Childhood House',
        ]);

        $this->assertEquals('110 Kingsbrook St.', $model->address->lineOne);

        $model->setRawAttributes([
            'address_line_one' => '117 Spencer St.',
            'address_line_two' => 'My Childhood House',
        ]);

        $this->assertEquals('117 Spencer St.', $model->address->lineOne);
    }
}

class TestEloquentModelWithCustomCast extends Model
{
    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

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

class HashCaster implements CastsInboundAttributes
{
    public function __construct($algorithm = 'sha256')
    {
        $this->algorithm = $algorithm;
    }

    public function set($model, $key, $value, $attributes)
    {
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
        return json_encode($value);
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
