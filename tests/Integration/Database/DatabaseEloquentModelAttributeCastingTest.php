<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class DatabaseEloquentModelAttributeCastingTest extends DatabaseTestCase
{
    protected function afterRefreshingDatabase()
    {
        Schema::create('test_eloquent_model_with_custom_casts', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
        });
    }

    public function testBasicCustomCasting()
    {
        $model = new TestEloquentModelWithAttributeCast;
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

        $model = new TestEloquentModelWithAttributeCast;
        $model->uppercase = 'taylor';
        $model->syncOriginal();
        $model->uppercase = 'dries';
        $model->getOriginal();

        $this->assertSame('DRIES', $model->uppercase);

        $model = $model->setAttribute('uppercase', 'james');

        $this->assertInstanceOf(TestEloquentModelWithAttributeCast::class, $model);

        $model = new TestEloquentModelWithAttributeCast;

        $model->address = $address = new AttributeCastAddress('110 Kingsbrook St.', 'My Childhood House');
        $address->lineOne = '117 Spencer St.';
        $this->assertSame('117 Spencer St.', $model->getAttributes()['address_line_one']);

        $model = new TestEloquentModelWithAttributeCast;

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

        $model = new TestEloquentModelWithAttributeCast(['options' => []]);
        $model->syncOriginal();
        $model->options = ['foo' => 'bar'];
        $this->assertTrue($model->isDirty('options'));

        $model = new TestEloquentModelWithAttributeCast;
        $model->birthday_at = now();
        $this->assertIsString($model->toArray()['birthday_at']);
    }

    public function testGetOriginalWithCastValueObjects()
    {
        $model = new TestEloquentModelWithAttributeCast([
            'address' => new AttributeCastAddress('110 Kingsbrook St.', 'My Childhood House'),
        ]);

        $model->syncOriginal();

        $model->address = new AttributeCastAddress('117 Spencer St.', 'Another house.');

        $this->assertSame('117 Spencer St.', $model->address->lineOne);
        $this->assertSame('110 Kingsbrook St.', $model->getOriginal('address')->lineOne);
        $this->assertSame('117 Spencer St.', $model->address->lineOne);

        $model = new TestEloquentModelWithAttributeCast([
            'address' => new AttributeCastAddress('110 Kingsbrook St.', 'My Childhood House'),
        ]);

        $model->syncOriginal();

        $model->address = new AttributeCastAddress('117 Spencer St.', 'Another house.');

        $this->assertSame('117 Spencer St.', $model->address->lineOne);
        $this->assertSame('110 Kingsbrook St.', $model->getOriginal()['address_line_one']);
        $this->assertSame('117 Spencer St.', $model->address->lineOne);
        $this->assertSame('110 Kingsbrook St.', $model->getOriginal()['address_line_one']);

        $model = new TestEloquentModelWithAttributeCast([
            'address' => new AttributeCastAddress('110 Kingsbrook St.', 'My Childhood House'),
        ]);

        $model->syncOriginal();

        $model->address = null;

        $this->assertNull($model->address);
        $this->assertInstanceOf(AttributeCastAddress::class, $model->getOriginal('address'));
        $this->assertNull($model->address);
    }

    public function testOneWayCasting()
    {
        $model = new TestEloquentModelWithAttributeCast;

        $this->assertNull($model->password);

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
        $model = new TestEloquentModelWithAttributeCast;

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

    public function testCastsThatOnlyHaveGetterDoNotPeristAnythingToModelOnSave()
    {
        $model = new TestEloquentModelWithAttributeCast;

        $model->virtual;

        $model->getAttributes();

        $this->assertEmpty($model->getDirty());
    }

    public function testCastsThatOnlyHaveGetterThatReturnsPrimitivesAreNotCached()
    {
        $model = new TestEloquentModelWithAttributeCast;

        $previous = null;

        foreach (range(0, 10) as $ignored) {
            $this->assertNotSame($previous, $previous = $model->virtualString);
        }
    }

    public function testAttributesCanCacheStrings()
    {
        $model = new TestEloquentModelWithAttributeCast;

        $previous = $model->virtual_string_cached;

        $this->assertIsString($previous);

        $this->assertSame($previous, $model->virtual_string_cached);
    }

    public function testAttributesCanCacheBooleans()
    {
        $model = new TestEloquentModelWithAttributeCast;

        $first = $model->virtual_boolean_cached;

        $this->assertIsBool($first);

        foreach (range(0, 10) as $ignored) {
            $this->assertSame($first, $model->virtual_boolean_cached);
        }
    }

    public function testAttributesCanCacheNull()
    {
        $model = new TestEloquentModelWithAttributeCast;

        $this->assertSame(0, $model->virtualNullCalls);

        $first = $model->virtual_null_cached;

        $this->assertNull($first);

        $this->assertSame(1, $model->virtualNullCalls);

        foreach (range(0, 10) as $ignored) {
            $this->assertSame($first, $model->virtual_null_cached);
        }

        $this->assertSame(1, $model->virtualNullCalls);
    }

    public function testAttributesByDefaultDontCacheBooleans()
    {
        $model = new TestEloquentModelWithAttributeCast;

        $first = $model->virtual_boolean;

        $this->assertIsBool($first);

        foreach (range(0, 50) as $ignored) {
            $current = $model->virtual_boolean;

            $this->assertIsBool($current);

            if ($first !== $current) {
                return;
            }
        }

        $this->fail('"virtual_boolean" seems to be cached.');
    }

    public function testCastsThatOnlyHaveGetterThatReturnsObjectAreCached()
    {
        $model = new TestEloquentModelWithAttributeCast;

        $previous = $model->virtualObject;

        foreach (range(0, 10) as $ignored) {
            $this->assertSame($previous, $previous = $model->virtualObject);
        }
    }

    public function testCastsThatOnlyHaveGetterThatReturnsDateTimeAreCached()
    {
        $model = new TestEloquentModelWithAttributeCast;

        $previous = $model->virtualDateTime;

        foreach (range(0, 10) as $ignored) {
            $this->assertSame($previous, $previous = $model->virtualDateTime);
        }
    }

    public function testCastsThatOnlyHaveGetterThatReturnsObjectAreNotCached()
    {
        $model = new TestEloquentModelWithAttributeCast;

        $previous = $model->virtualObjectWithoutCaching;

        foreach (range(0, 10) as $ignored) {
            $this->assertNotSame($previous, $previous = $model->virtualObjectWithoutCaching);
        }
    }

    public function testCastsThatOnlyHaveGetterThatReturnsDateTimeAreNotCached()
    {
        $model = new TestEloquentModelWithAttributeCast;

        $previous = $model->virtualDateTimeWithoutCaching;

        foreach (range(0, 10) as $ignored) {
            $this->assertNotSame($previous, $previous = $model->virtualDateTimeWithoutCaching);
        }
    }

    public function testCastsThatOnlyHaveGetterThatReturnsObjectAreNotCachedFluent()
    {
        $model = new TestEloquentModelWithAttributeCast;

        $previous = $model->virtualObjectWithoutCachingFluent;

        foreach (range(0, 10) as $ignored) {
            $this->assertNotSame($previous, $previous = $model->virtualObjectWithoutCachingFluent);
        }
    }

    public function testCastsThatOnlyHaveGetterThatReturnsDateTimeAreNotCachedFluent()
    {
        $model = new TestEloquentModelWithAttributeCast;

        $previous = $model->virtualDateTimeWithoutCachingFluent;

        foreach (range(0, 10) as $ignored) {
            $this->assertNotSame($previous, $previous = $model->virtualDateTimeWithoutCachingFluent);
        }
    }
}

class TestEloquentModelWithAttributeCast extends Model
{
    /**
     * The attributes that aren't mass assignable.
     *
     * @var string[]
     */
    protected $guarded = [];

    public function uppercase(): Attribute
    {
        return Attribute::make(
            function ($value) {
                return strtoupper($value);
            },
            function ($value) {
                return strtoupper($value);
            }
        );
    }

    public function address(): Attribute
    {
        return new Attribute(
            function ($value, $attributes) {
                if (is_null($attributes['address_line_one'])) {
                    return;
                }

                return new AttributeCastAddress($attributes['address_line_one'], $attributes['address_line_two']);
            },
            function ($value) {
                if (is_null($value)) {
                    return [
                        'address_line_one' => null,
                        'address_line_two' => null,
                    ];
                }

                return ['address_line_one' => $value->lineOne, 'address_line_two' => $value->lineTwo];
            }
        );
    }

    public function options(): Attribute
    {
        return new Attribute(
            function ($value) {
                return json_decode($value, true);
            },
            function ($value) {
                return json_encode($value);
            }
        );
    }

    public function birthdayAt(): Attribute
    {
        return new Attribute(
            function ($value) {
                return Carbon::parse($value);
            },
            function ($value) {
                return $value->format('Y-m-d');
            }
        );
    }

    public function password(): Attribute
    {
        return new Attribute(null, function ($value) {
            return hash('sha256', $value);
        });
    }

    public function virtual(): Attribute
    {
        return new Attribute(
            function () {
                return collect();
            }
        );
    }

    public function virtualString(): Attribute
    {
        return new Attribute(
            function () {
                return Str::random(10);
            }
        );
    }

    public function virtualStringCached(): Attribute
    {
        return Attribute::get(function () {
            return Str::random(10);
        })->shouldCache();
    }

    public function virtualBooleanCached(): Attribute
    {
        return Attribute::get(function () {
            return (bool) mt_rand(0, 1);
        })->shouldCache();
    }

    public function virtualBoolean(): Attribute
    {
        return Attribute::get(function () {
            return (bool) mt_rand(0, 1);
        });
    }

    public $virtualNullCalls = 0;

    public function virtualNullCached(): Attribute
    {
        return Attribute::get(function () {
            $this->virtualNullCalls++;

            return null;
        })->shouldCache();
    }

    public function virtualObject(): Attribute
    {
        return new Attribute(
            function () {
                return new AttributeCastAddress(Str::random(10), Str::random(10));
            }
        );
    }

    public function virtualDateTime(): Attribute
    {
        return new Attribute(
            function () {
                return Date::now()->addSeconds(mt_rand(0, 10000));
            }
        );
    }

    public function virtualObjectWithoutCachingFluent(): Attribute
    {
        return (new Attribute(
            function () {
                return new AttributeCastAddress(Str::random(10), Str::random(10));
            }
        ))->withoutObjectCaching();
    }

    public function virtualDateTimeWithoutCachingFluent(): Attribute
    {
        return (new Attribute(
            function () {
                return Date::now()->addSeconds(mt_rand(0, 10000));
            }
        ))->withoutObjectCaching();
    }

    public function virtualObjectWithoutCaching(): Attribute
    {
        return Attribute::get(function () {
            return new AttributeCastAddress(Str::random(10), Str::random(10));
        })->withoutObjectCaching();
    }

    public function virtualDateTimeWithoutCaching(): Attribute
    {
        return Attribute::get(function () {
            return Date::now()->addSeconds(mt_rand(0, 10000));
        })->withoutObjectCaching();
    }
}

class AttributeCastAddress
{
    public $lineOne;
    public $lineTwo;

    public function __construct($lineOne, $lineTwo)
    {
        $this->lineOne = $lineOne;
        $this->lineTwo = $lineTwo;
    }
}
