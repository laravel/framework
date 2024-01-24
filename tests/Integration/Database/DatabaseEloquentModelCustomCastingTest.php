<?php

namespace Illuminate\Tests\Integration\Database;

use Exception;
use Illuminate\Contracts\Database\Eloquent\Castable;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Database\Eloquent\CastsInboundAttributes;
use Illuminate\Contracts\Database\Eloquent\DeviatesCastableAttributes;
use Illuminate\Contracts\Database\Eloquent\SerializesCastableAttributes;
use Illuminate\Database\Eloquent\InvalidCastException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;

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

        $model->address = $address = new Address('110 Kingsbrook St.', 'My Childhood House');
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
        $model->birthday_at = now();
        $this->assertIsString($model->toArray()['birthday_at']);

        $model = new TestEloquentModelWithCustomCast;
        $now = now()->toImmutable();
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
            'address' => new Address('110 Kingsbrook St.', 'My Childhood House'),
        ]);

        $model->syncOriginal();

        $model->address = new Address('117 Spencer St.', 'Another house.');

        $this->assertSame('117 Spencer St.', $model->address->lineOne);
        $this->assertSame('110 Kingsbrook St.', $model->getOriginal('address')->lineOne);
        $this->assertSame('117 Spencer St.', $model->address->lineOne);

        $model = new TestEloquentModelWithCustomCast([
            'address' => new Address('110 Kingsbrook St.', 'My Childhood House'),
        ]);

        $model->syncOriginal();

        $model->address = new Address('117 Spencer St.', 'Another house.');

        $this->assertSame('117 Spencer St.', $model->address->lineOne);
        $this->assertSame('110 Kingsbrook St.', $model->getOriginal()['address_line_one']);
        $this->assertSame('117 Spencer St.', $model->address->lineOne);
        $this->assertSame('110 Kingsbrook St.', $model->getOriginal()['address_line_one']);

        $model = new TestEloquentModelWithCustomCast([
            'address' => new Address('110 Kingsbrook St.', 'My Childhood House'),
        ]);

        $model->syncOriginal();

        $model->address = null;

        $this->assertNull($model->address);
        $this->assertInstanceOf(Address::class, $model->getOriginal('address'));
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
}

class TestEloquentModelWithCustomCast extends Model
{
    /**
     * The attributes that aren't mass assignable.
     *
     * @var string[]
     */
    protected $guarded = [];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'address' => AddressCaster::class,
        'price' => DecimalCaster::class,
        'password' => HashCaster::class,
        'other_password' => HashCaster::class.':md5',
        'uppercase' => UppercaseCaster::class,
        'options' => JsonCaster::class,
        'typed_settings' => JsonSettingsCaster::class,
        'value_object_with_caster' => ValueObject::class,
        'value_object_caster_with_argument' => ValueObject::class.':argument',
        'value_object_caster_with_caster_instance' => ValueObjectWithCasterInstance::class,
        'undefined_cast_column' => UndefinedCast::class,
        'birthday_at' => DateObjectCaster::class,
        'anniversary_on_with_object_caching' => DateTimezoneCasterWithObjectCaching::class.':America/New_York',
        'anniversary_on_without_object_caching' => DateTimezoneCasterWithoutObjectCaching::class.':America/New_York',
    ];
}

class HashCaster implements CastsInboundAttributes
{
    protected $algorithm;

    public function __construct($algorithm = 'sha256')
    {
        $this->algorithm = $algorithm;
    }

    public function set($model, $key, $value, $attributes)
    {
        return [$key => hash($this->algorithm, $value)];
    }
}

class UppercaseCaster implements CastsAttributes
{
    public function get($model, $key, $value, $attributes)
    {
        return strtoupper($value);
    }

    public function set($model, $key, $value, $attributes)
    {
        return [$key => strtoupper($value)];
    }
}

class AddressCaster implements CastsAttributes
{
    public function get($model, $key, $value, $attributes)
    {
        if (is_null($attributes['address_line_one'])) {
            return;
        }

        return new Address($attributes['address_line_one'], $attributes['address_line_two']);
    }

    public function set($model, $key, $value, $attributes)
    {
        if (is_null($value)) {
            return [
                'address_line_one' => null,
                'address_line_two' => null,
            ];
        }

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

class JsonSettingsCaster implements CastsAttributes
{
    public function get($model, string $key, $value, array $attributes): ?Settings
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof Settings) {
            return $value;
        }

        $payload = json_decode($value, true, JSON_THROW_ON_ERROR);

        return Settings::from($payload);
    }

    public function set($model, string $key, $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_array($value)) {
            $value = Settings::from($value);
        }

        if (! $value instanceof Settings) {
            throw new Exception("Attribute `{$key}` with JsonSettingsCaster should be a Settings object");
        }

        return $value->toJson();
    }
}

class DecimalCaster implements CastsAttributes, DeviatesCastableAttributes, SerializesCastableAttributes
{
    public function get($model, $key, $value, $attributes)
    {
        return new Decimal($value);
    }

    public function set($model, $key, $value, $attributes)
    {
        return (string) $value;
    }

    public function increment($model, $key, $value, $attributes)
    {
        return new Decimal($attributes[$key] + $value);
    }

    public function decrement($model, $key, $value, $attributes)
    {
        return new Decimal($attributes[$key] - $value);
    }

    public function serialize($model, $key, $value, $attributes)
    {
        return (string) $value;
    }
}

class ValueObjectCaster implements CastsAttributes
{
    private $argument;

    public function __construct($argument = null)
    {
        $this->argument = $argument;
    }

    public function get($model, $key, $value, $attributes)
    {
        if ($this->argument) {
            return $this->argument;
        }

        return unserialize($value);
    }

    public function set($model, $key, $value, $attributes)
    {
        return serialize($value);
    }
}

class ValueObject implements Castable
{
    public $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public static function castUsing(array $arguments)
    {
        return new class(...$arguments) implements CastsAttributes, SerializesCastableAttributes
        {
            private $argument;

            public function __construct($argument = null)
            {
                $this->argument = $argument;
            }

            public function get($model, $key, $value, $attributes)
            {
                if ($this->argument) {
                    return $this->argument;
                }

                return unserialize($value);
            }

            public function set($model, $key, $value, $attributes)
            {
                return serialize($value);
            }

            public function serialize($model, $key, $value, $attributes)
            {
                return serialize($value);
            }
        };
    }
}

class ValueObjectWithCasterInstance extends ValueObject
{
    public static function castUsing(array $arguments)
    {
        return new ValueObjectCaster;
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

class Settings
{
    public ?bool $foo;
    public ?bool $bar;

    public function __construct(?bool $foo, ?bool $bar)
    {
        $this->foo = $foo;
        $this->bar = $bar;
    }

    public static function from(array $data): Settings
    {
        return new self(
            $data['foo'] ?? null,
            $data['bar'] ?? null,
        );
    }

    public function toJson($options = 0): string
    {
        return json_encode(['foo' => $this->foo, 'bar' => $this->bar], $options);
    }
}

final class Decimal
{
    private $value;
    private $scale;

    public function __construct($value)
    {
        $parts = explode('.', (string) $value);

        $this->scale = strlen($parts[1]);
        $this->value = (int) str_replace('.', '', $value);
    }

    public function getValue()
    {
        return $this->value;
    }

    public function __toString()
    {
        return substr_replace($this->value, '.', -$this->scale, 0);
    }
}

class DateObjectCaster implements CastsAttributes
{
    private $argument;

    public function __construct($argument = null)
    {
        $this->argument = $argument;
    }

    public function get($model, $key, $value, $attributes)
    {
        return Carbon::parse($value);
    }

    public function set($model, $key, $value, $attributes)
    {
        return $value->format('Y-m-d');
    }
}

class DateTimezoneCasterWithObjectCaching implements CastsAttributes
{
    public function __construct(private string $timezone = 'UTC')
    {
    }

    public function get($model, $key, $value, $attributes)
    {
        return Carbon::parse($value, $this->timezone);
    }

    public function set($model, $key, $value, $attributes)
    {
        return $value->timezone($this->timezone)->format('Y-m-d');
    }
}

class DateTimezoneCasterWithoutObjectCaching extends DateTimezoneCasterWithObjectCaching
{
    public bool $withoutObjectCaching = true;
}
