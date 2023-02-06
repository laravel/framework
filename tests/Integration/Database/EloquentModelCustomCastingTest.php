<?php

namespace Illuminate\Tests\Integration\Database;

use Brick\Math\BigNumber;
use GMP;
use Illuminate\Contracts\Database\Eloquent\Castable;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Database\Eloquent\SerializesCastableAttributes;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\Schema\Blueprint;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * @group integration
 */
class EloquentModelCustomCastingTest extends TestCase
{
    protected function setUp(): void
    {
        $db = new DB;

        $db->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);

        $db->bootEloquent();
        $db->setAsGlobal();

        $this->createSchema();
    }

    /**
     * Setup the database schema.
     *
     * @return void
     */
    public function createSchema()
    {
        $this->schema()->create('casting_table', function (Blueprint $table) {
            $table->increments('id');
            $table->string('address_line_one');
            $table->string('address_line_two');
            $table->integer('amount');
            $table->string('string_field');
            $table->timestamps();
        });

        $this->schema()->create('members', function (Blueprint $table) {
            $table->increments('id');
            $table->decimal('amount', 4, 2);
        });
    }

    /**
     * Tear down the database schema.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        $this->schema()->drop('casting_table');
        $this->schema()->drop('members');
    }

    /**
     * @requires extension gmp
     */
    public function testSavingCastedAttributesToDatabase()
    {
        /** @var \Illuminate\Tests\Integration\Database\CustomCasts $model */
        $model = CustomCasts::create([
            'address' => new AddressModel('address_line_one_value', 'address_line_two_value'),
            'amount' => gmp_init('1000', 10),
            'string_field' => null,
        ]);

        $this->assertSame('address_line_one_value', $model->getOriginal('address_line_one'));
        $this->assertSame('address_line_one_value', $model->getAttribute('address_line_one'));

        $this->assertSame('address_line_two_value', $model->getOriginal('address_line_two'));
        $this->assertSame('address_line_two_value', $model->getAttribute('address_line_two'));

        $this->assertSame('1000', $model->getRawOriginal('amount'));

        $this->assertNull($model->getOriginal('string_field'));
        $this->assertNull($model->getAttribute('string_field'));
        $this->assertSame('', $model->getRawOriginal('string_field'));

        /** @var \Illuminate\Tests\Integration\Database\CustomCasts $another_model */
        $another_model = CustomCasts::create([
            'address_line_one' => 'address_line_one_value',
            'address_line_two' => 'address_line_two_value',
            'amount' => gmp_init('500', 10),
            'string_field' => 'string_value',
        ]);

        $this->assertInstanceOf(AddressModel::class, $another_model->address);

        $this->assertSame('address_line_one_value', $model->address->lineOne);
        $this->assertSame('address_line_two_value', $model->address->lineTwo);
        $this->assertInstanceOf(GMP::class, $model->amount);
    }

    /**
     * @requires extension gmp
     */
    public function testInvalidArgumentExceptionOnInvalidValue()
    {
        /** @var \Illuminate\Tests\Integration\Database\CustomCasts $model */
        $model = CustomCasts::create([
            'address' => new AddressModel('address_line_one_value', 'address_line_two_value'),
            'amount' => gmp_init('1000', 10),
            'string_field' => 'string_value',
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The given value is not an Address instance.');
        $model->address = 'single_string';

        // Ensure model values remain unchanged
        $this->assertSame('address_line_one_value', $model->address->lineOne);
        $this->assertSame('address_line_two_value', $model->address->lineTwo);
    }

    /**
     * @requires extension gmp
     */
    public function testInvalidArgumentExceptionOnNull()
    {
        /** @var \Illuminate\Tests\Integration\Database\CustomCasts $model */
        $model = CustomCasts::create([
            'address' => new AddressModel('address_line_one_value', 'address_line_two_value'),
            'amount' => gmp_init('1000', 10),
            'string_field' => 'string_value',
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The given value is not an Address instance.');
        $model->address = null;

        // Ensure model values remain unchanged
        $this->assertSame('address_line_one_value', $model->address->lineOne);
        $this->assertSame('address_line_two_value', $model->address->lineTwo);
    }

    /**
     * @requires extension gmp
     */
    public function testModelsWithCustomCastsCanBeConvertedToArrays()
    {
        /** @var \Illuminate\Tests\Integration\Database\CustomCasts $model */
        $model = CustomCasts::create([
            'address' => new AddressModel('address_line_one_value', 'address_line_two_value'),
            'amount' => gmp_init('1000', 10),
            'string_field' => 'string_value',
        ]);

        // Ensure model values remain unchanged
        $this->assertSame([
            'address_line_one' => 'address_line_one_value',
            'address_line_two' => 'address_line_two_value',
            'amount' => '1000',
            'string_field' => 'string_value',
            'updated_at' => $model->updated_at->toJSON(),
            'created_at' => $model->created_at->toJSON(),
            'id' => 1,
        ], $model->toArray());
    }

    public function testModelWithCustomCastsWorkWithCustomIncrementDecrement()
    {
        $model = new Member();
        $model->amount = new Euro('2');
        $model->save();

        $this->assertInstanceOf(Euro::class, $model->amount);
        $this->assertEquals('2', $model->amount->value);

        $model->incrementAmount(new Euro('1'));
        $this->assertEquals('3.00', $model->amount->value);
    }

    /**
     * Get a database connection instance.
     *
     * @return \Illuminate\Database\Connection
     */
    protected function connection()
    {
        return Eloquent::getConnectionResolver()->connection();
    }

    /**
     * Get a schema builder instance.
     *
     * @return \Illuminate\Database\Schema\Builder
     */
    protected function schema()
    {
        return $this->connection()->getSchemaBuilder();
    }
}

/**
 * Eloquent Casts...
 */
class AddressCast implements CastsAttributes
{
    /**
     * Cast the given value.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  string  $key
     * @param  mixed  $value
     * @param  array  $attributes
     * @return \Illuminate\Tests\Integration\Database\AddressModel
     */
    public function get($model, $key, $value, $attributes)
    {
        return new AddressModel(
            $attributes['address_line_one'],
            $attributes['address_line_two'],
        );
    }

    /**
     * Prepare the given value for storage.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  string  $key
     * @param  AddressModel  $value
     * @param  array  $attributes
     * @return array
     */
    public function set($model, $key, $value, $attributes)
    {
        if (! $value instanceof AddressModel) {
            throw new InvalidArgumentException('The given value is not an Address instance.');
        }

        return [
            'address_line_one' => $value->lineOne,
            'address_line_two' => $value->lineTwo,
        ];
    }
}

class GMPCast implements CastsAttributes, SerializesCastableAttributes
{
    /**
     * Cast the given value.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  string  $key
     * @param  string  $value
     * @param  array  $attributes
     * @return string|null
     */
    public function get($model, $key, $value, $attributes)
    {
        return gmp_init($value, 10);
    }

    /**
     * Prepare the given value for storage.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  string  $key
     * @param  string|null  $value
     * @param  array  $attributes
     * @return string
     */
    public function set($model, $key, $value, $attributes)
    {
        return gmp_strval($value, 10);
    }

    /**
     * Serialize the attribute when converting the model to an array.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  string  $key
     * @param  mixed  $value
     * @param  array  $attributes
     * @return mixed
     */
    public function serialize($model, string $key, $value, array $attributes)
    {
        return gmp_strval($value, 10);
    }
}

class NonNullableString implements CastsAttributes
{
    /**
     * Cast the given value.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  string  $key
     * @param  string  $value
     * @param  array  $attributes
     * @return string|null
     */
    public function get($model, $key, $value, $attributes)
    {
        return ($value != '') ? $value : null;
    }

    /**
     * Prepare the given value for storage.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  string  $key
     * @param  string|null  $value
     * @param  array  $attributes
     * @return string
     */
    public function set($model, $key, $value, $attributes)
    {
        return $value ?? '';
    }
}

/**
 * Eloquent Models...
 */
class CustomCasts extends Eloquent
{
    /**
     * @var string
     */
    protected $table = 'casting_table';

    /**
     * @var string[]
     */
    protected $guarded = [];

    /**
     * @var array
     */
    protected $casts = [
        'address' => AddressCast::class,
        'amount' => GMPCast::class,
        'string_field' => NonNullableString::class,
    ];
}

class AddressModel
{
    /**
     * @var string
     */
    public $lineOne;

    /**
     * @var string
     */
    public $lineTwo;

    public function __construct($address_line_one, $address_line_two)
    {
        $this->lineOne = $address_line_one;
        $this->lineTwo = $address_line_two;
    }
}

class Euro implements Castable
{
    public string $value;

    public function __construct(string $value)
    {
        $this->value = $value;
    }

    public static function castUsing(array $arguments)
    {
        return EuroCaster::class;
    }
}

class EuroCaster implements CastsAttributes
{
    public function get($model, $key, $value, $attributes)
    {
        return new Euro($value);
    }

    public function set($model, $key, $value, $attributes)
    {
        return $value->value;
    }

    public function increment($model, $key, string $value, $attributes)
    {
        $model->$key = new Euro((string) BigNumber::of($model->$key->value)->plus($value)->toScale(2));

        return $model->$key;
    }

    public function decrement($model, $key, string $value, $attributes)
    {
        $model->$key = new Euro((string) BigNumber::of($model->$key->value)->subtract($value)->toScale(2));

        return $model->$key;
    }
}

class Member extends Model
{
    public $timestamps = false;
    protected $casts = [
        'amount' => Euro::class,
    ];

    public function incrementAmount(Euro $amount)
    {
        $this->increment('amount', $amount->value);
    }
}
