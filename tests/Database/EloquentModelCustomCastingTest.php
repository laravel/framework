<?php

namespace Illuminate\Tests\Database;

use Brick\Math\BigNumber;
use GMP;
use Illuminate\Contracts\Database\Eloquent\Castable;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Database\Eloquent\ComparesCastableAttributes;
use Illuminate\Contracts\Database\Eloquent\SerializesCastableAttributes;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\MassAssignmentException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\Schema\Blueprint;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('integration')]
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

        $this->schema()->create('documents', function (Blueprint $table) {
            $table->increments('id');
            $table->json('document');
        });

        $this->schema()->create('people', function (Blueprint $table) {
            $table->increments('id');
            $table->string('address_line_one');
            $table->string('address_line_two');
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
        $this->schema()->drop('documents');
    }

    #[RequiresPhpExtension('gmp')]
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

    #[RequiresPhpExtension('gmp')]
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

    #[RequiresPhpExtension('gmp')]
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

    #[RequiresPhpExtension('gmp')]
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

        $model->increment('amount', new Euro('1'));
        $this->assertEquals('3.00', $model->amount->value);
    }

    public function testModelWithCustomCastsCompareFunction()
    {
        // Set raw attribute, this is an example of how we would receive JSON string from the database.
        // Note the spaces after the colon.
        $model = new Document();
        $model->setRawAttributes(['document' => '{"content": "content", "title": "hello world"}']);
        $model->save();

        // Inverse title and content this would result in a different JSON string when json_encode is used
        $document = new \stdClass();
        $document->title = 'hello world';
        $document->content = 'content';
        $model->document = $document;

        $this->assertFalse($model->isDirty('document'));
        $document->title = 'hello world 2';
        $this->assertTrue($model->isDirty('document'));
    }

    public function testModelWithCustomCastsUnguardedCanBeMassAssigned()
    {
        Person::preventSilentlyDiscardingAttributes();

        $model = Person::create(['address' => new AddressDto('123 Main St.', 'Anytown, USA')]);
        $this->assertSame('123 Main St.', $model->address->lineOne);
        $this->assertSame('Anytown, USA', $model->address->lineTwo);
    }

    public function testModelWithCustomCastsCanBeGuardedAgainstMassAssigned()
    {
        Person::preventSilentlyDiscardingAttributes();
        $this->expectException(MassAssignmentException::class);

        $model = new Person();
        $model->guard(['address']);
        $model->create(['id' => 1, 'address' => new AddressDto('123 Main St.', 'Anytown, USA')]);
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
        return $value instanceof Euro ? $value->value : $value;
    }

    public function increment($model, $key, $value, $attributes)
    {
        $model->$key = new Euro((string) BigNumber::of($model->$key->value)->plus($value->value)->toScale(2));

        return $model->$key;
    }

    public function decrement($model, $key, $value, $attributes)
    {
        $model->$key = new Euro((string) BigNumber::of($model->$key->value)->subtract($value->value)->toScale(2));

        return $model->$key;
    }
}

class Member extends Model
{
    public $timestamps = false;
    protected $casts = [
        'amount' => Euro::class,
    ];
}

class Document extends Model
{
    public $timestamps = false;

    protected $casts = [
        'document' => StructuredDocumentCaster::class,
    ];
}

class Person extends Model
{
    protected $guarded = ['id'];
    public $timestamps = false;
    protected $casts = [
        'address' => AsAddress::class,
    ];
}

class StructuredDocumentCaster implements CastsAttributes, ComparesCastableAttributes
{
    public function get($model, $key, $value, $attributes)
    {
        return json_decode($value);
    }

    public function set($model, $key, $value, $attributes)
    {
        return json_encode($value);
    }

    public function compare($model, $key, $value1, $value2)
    {
        return json_decode($value1) == json_decode($value2);
    }
}

class AddressDto
{
    public function __construct(public string $lineOne, public string $lineTwo)
    {
        //
    }
}

class AsAddress implements CastsAttributes
{
    public function get($model, $key, $value, $attributes)
    {
        return new AddressDto($attributes['address_line_one'], $attributes['address_line_two']);
    }

    public function set($model, $key, $value, $attributes)
    {
        return ['address_line_one' => $value->lineOne, 'address_line_two' => $value->lineTwo];
    }
}
