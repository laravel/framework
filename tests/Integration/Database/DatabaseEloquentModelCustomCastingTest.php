<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Database\Eloquent\InvalidCastException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Tests\App\Models\Casts\TestEloquentModelWithCustomCast;
use Illuminate\Tests\App\ValueObjects\AddressCastValue;
use Illuminate\Tests\App\ValueObjects\Decimal;
use Illuminate\Tests\App\ValueObjects\ValueObject;

class DatabaseEloquentModelCustomCastingTest extends DatabaseTestCase
{
    protected function afterRefreshingDatabase()
    {
        Schema::create('test_eloquent_model_with_custom_casts', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
            $table->decimal('price');
        });
    }

    public function testBasicCustomCasting()
    {
        $model = new TestEloquentModelWithCustomCast;
        $model->uppercase = 'taylor';

        $this->assertSame('TAYLOR', $model->uppercase);
        $this->assertSame('TAYLOR', $model->getAttributes()['uppercase']);
        $this->assertSame('TAYLOR', $model->toArray()['uppercase']);

        $unserializedModel = unserialize(serialize($model));

        $this->assertSame('TAYLOR', $unserializedModel->uppercase);
        $this->assertSame('TAYLOR', $unserializedModel->getAttributes()['uppercase']);
        $this->assertSame('TAYLOR', $unserializedModel->toArray()['uppercase']);

        $model->syncOriginal();
        $model->uppercase = 'dries';
        $this->assertSame('TAYLOR', $model->getOriginal('uppercase'));

        $model = new TestEloquentModelWithCustomCast;
        $model->uppercase = 'taylor';
        $model->syncOriginal();
        $model->uppercase = 'dries';
        $model->getOriginal();

        $this->assertSame('DRIES', $model->uppercase);

        $model = new TestEloquentModelWithCustomCast;

        $model->address = $address = new AddressCastValue('110 Kingsbrook St.', 'My Childhood House');
        $address->lineOne = '117 Spencer St.';
        $this->assertSame('117 Spencer St.', $model->getAttributes()['address_line_one']);

        $model = new TestEloquentModelWithCustomCast;

        $model->setRawAttributes([
            'address_line_one' => '110 Kingsbrook St.',
            'address_line_two' => 'My Childhood House',
        ]);

        $this->assertSame('110 Kingsbrook St.', $model->address->lineOne);
        $this->assertSame('My Childhood House', $model->address->lineTwo);

        $this->assertSame('110 Kingsbrook St.', $model->toArray()['address_line_one']);
        $this->assertSame('My Childhood House', $model->toArray()['address_line_two']);

        $model->address->lineOne = '117 Spencer St.';

        $this->assertFalse(isset($model->toArray()['address']));
        $this->assertSame('117 Spencer St.', $model->toArray()['address_line_one']);
        $this->assertSame('My Childhood House', $model->toArray()['address_line_two']);

        $this->assertSame('117 Spencer St.', json_decode($model->toJson(), true)['address_line_one']);
        $this->assertSame('My Childhood House', json_decode($model->toJson(), true)['address_line_two']);

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

        $this->assertSame(json_encode(['foo' => 'bar']), $model->getAttributes()['options']);

        $model = new TestEloquentModelWithCustomCast(['options' => []]);
        $model->syncOriginal();
        $model->options = ['foo' => 'bar'];
        $this->assertTrue($model->isDirty('options'));

        $model = new TestEloquentModelWithCustomCast;
        $model->birthday_at = Carbon::now();
        $this->assertIsString($model->toArray()['birthday_at']);

        $model = new TestEloquentModelWithCustomCast;
        $now = Carbon::now()->toImmutable();
        $model->anniversary_on_with_object_caching = $now;
        $model->anniversary_on_without_object_caching = $now;
        $this->assertSame($now, $model->anniversary_on_with_object_caching);
        $this->assertSame('UTC', $model->anniversary_on_with_object_caching->format('e'));
        $this->assertNotSame($now, $model->anniversary_on_without_object_caching);
        $this->assertNotSame('UTC', $model->anniversary_on_without_object_caching->format('e'));
    }

    public function testGetOriginalWithCastValueObjects()
    {
        $model = new TestEloquentModelWithCustomCast([
            'address' => new AddressCastValue('110 Kingsbrook St.', 'My Childhood House'),
        ]);

        $model->syncOriginal();

        $model->address = new AddressCastValue('117 Spencer St.', 'Another house.');

        $this->assertSame('117 Spencer St.', $model->address->lineOne);
        $this->assertSame('110 Kingsbrook St.', $model->getOriginal('address')->lineOne);
        $this->assertSame('117 Spencer St.', $model->address->lineOne);

        $model = new TestEloquentModelWithCustomCast([
            'address' => new AddressCastValue('110 Kingsbrook St.', 'My Childhood House'),
        ]);

        $model->syncOriginal();

        $model->address = new AddressCastValue('117 Spencer St.', 'Another house.');

        $this->assertSame('117 Spencer St.', $model->address->lineOne);
        $this->assertSame('110 Kingsbrook St.', $model->getOriginal()['address_line_one']);
        $this->assertSame('117 Spencer St.', $model->address->lineOne);
        $this->assertSame('110 Kingsbrook St.', $model->getOriginal()['address_line_one']);

        $model = new TestEloquentModelWithCustomCast([
            'address' => new AddressCastValue('110 Kingsbrook St.', 'My Childhood House'),
        ]);

        $model->syncOriginal();

        $model->address = null;

        $this->assertNull($model->address);
        $this->assertInstanceOf(AddressCastValue::class, $model->getOriginal('address'));
        $this->assertNull($model->address);
    }

    public function testDeviableCasts()
    {
        $model = new TestEloquentModelWithCustomCast;
        $model->price = '123.456';
        $model->save();

        $model->increment('price', '530.865');

        $this->assertSame((new Decimal('654.321'))->getValue(), $model->price->getValue());

        $model->decrement('price', '333.333');

        $this->assertSame((new Decimal('320.988'))->getValue(), $model->price->getValue());

        $model->increment('price', new Decimal('100.001'));

        $this->assertSame((new Decimal('420.989'))->getValue(), $model->price->getValue());

        $model->decrement('price', new Decimal('200.002'));

        $this->assertSame((new Decimal('220.987'))->getValue(), $model->price->getValue());
    }

    public function testSerializableCasts()
    {
        $model = new TestEloquentModelWithCustomCast;
        $model->price = '123.456';

        $expectedValue = (new Decimal('123.456'))->getValue();

        $this->assertSame($expectedValue, $model->price->getValue());
        $this->assertSame('123.456', $model->getAttributes()['price']);
        $this->assertSame('123.456', $model->toArray()['price']);

        $unserializedModel = unserialize(serialize($model));

        $this->assertSame($expectedValue, $unserializedModel->price->getValue());
        $this->assertSame('123.456', $unserializedModel->getAttributes()['price']);
        $this->assertSame('123.456', $unserializedModel->toArray()['price']);
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

        $this->assertSame('110 Kingsbrook St.', $model->address->lineOne);

        $model->setRawAttributes([
            'address_line_one' => '117 Spencer St.',
            'address_line_two' => 'My Childhood House',
        ]);

        $this->assertSame('117 Spencer St.', $model->address->lineOne);
    }

    public function testSettingAttributesUsingArrowClearsTheCastCache()
    {
        $model = new TestEloquentModelWithCustomCast;
        $model->typed_settings = ['foo' => true];

        $this->assertTrue($model->typed_settings->foo);

        $model->setAttribute('typed_settings->foo', false);

        $this->assertFalse($model->typed_settings->foo);
    }

    public function testWithCastableInterface()
    {
        $model = new TestEloquentModelWithCustomCast;

        $model->setRawAttributes([
            'value_object_with_caster' => serialize(new ValueObject('hello')),
        ]);

        $this->assertInstanceOf(ValueObject::class, $model->value_object_with_caster);
        $this->assertSame(serialize(new ValueObject('hello')), $model->toArray()['value_object_with_caster']);

        $model->setRawAttributes([
            'value_object_caster_with_argument' => null,
        ]);

        $this->assertSame('argument', $model->value_object_caster_with_argument);

        $model->setRawAttributes([
            'value_object_caster_with_caster_instance' => serialize(new ValueObject('hello')),
        ]);

        $this->assertInstanceOf(ValueObject::class, $model->value_object_caster_with_caster_instance);
    }

    public function testGetFromUndefinedCast()
    {
        $this->expectException(InvalidCastException::class);

        $model = new TestEloquentModelWithCustomCast;
        $model->undefined_cast_column;
    }

    public function testSetToUndefinedCast()
    {
        $this->expectException(InvalidCastException::class);

        $model = new TestEloquentModelWithCustomCast;
        $this->assertTrue($model->hasCast('undefined_cast_column'));

        $model->undefined_cast_column = 'Glāžšķūņu rūķīši';
    }

    public function testMutatorCanDependOnAnotherCastedAttribute()
    {
        $model = new TestEloquentModelWithCustomCast([
            'address_line_one' => '110 Kingsbrook St.',
            'address_line_two' => 'My Childhood House',
        ]);
        $model->address->lineOne = 'Changed St.';
        $this->assertSame('Changed St. (My Childhood House)', $model->address_string);
    }

    public function testMutatorCanDependOnAnotherCastedCarbonAttribute()
    {
        $model = new TestEloquentModelWithCustomCast([
            'dob' => '2000-11-11',
            'tob' => '2000-11-11 11:11:00',
        ]);

        $model->dob->addDay();
        $this->assertSame('2000-11-12 11:11:00', $model->tob);
    }
}
