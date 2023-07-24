<?php

namespace Illuminate\Tests\Database;

use ArrayObject;
use Carbon\CarbonImmutable;
use Faker\Generator;
use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Casts\AsEncryptedArrayObject;
use Illuminate\Database\Eloquent\Casts\AsEncryptedCollection;
use Illuminate\Database\Eloquent\Casts\AsEnumCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\QueryException;
use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class RealTimeFactoryTest extends TestCase
{
    protected $container;

    protected $app;

    protected function setUp(): void
    {
        User::encryptUsing($encrypter = new Encrypter(Str::random()));
        Crypt::swap($encrypter);

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
        $this->schema()->create('users', function ($table) {
            $table->increments('id');
            $table->text('array_column');
            $table->text('json_column');
            $table->text('object_column');
            $table->text('collection_column');
            $table->text('encrypted_array_column');
            $table->text('encrypted_collection_column');
            $table->text('encrypted_json_column');
            $table->text('encrypted_object_column');
            $table->text('as_array_object_column');
            $table->text('as_collection_column');
            $table->text('as_encrypted_array_object_column');
            $table->text('as_encrypted_collection_column');
            $table->dateTime('datetime_column');
            $table->date('date_column');
            $table->datetime('immutable_datetime_column');
            $table->date('immutable_date_column');
            $table->datetime('datetime_custom_column');
            $table->integer('integer_column');
            $table->float('float_column');
            $table->double('double_column');
            $table->decimal('decimal_column');
            $table->boolean('boolean_column');
            $table->timestamp('timestamp_column');
            $table->string('string_column');
            $table->enum('enum_column', ['FOO', 'BAR']);
            $table->text('enum_collection_column');
            $table->enum('backed_enum_column', ['foo', 'bar']);
            $table->text('backed_enum_collection_column');
            $table->timestamps();
        });

        $this->schema()->create('guess_users', function ($table) {
            $table->increments('id');
            $table->string('email');
            $table->string('email_address');
            $table->string('name');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('username');
            $table->timestamps();
        });

        $this->schema()->create('posts', function ($table) {
            $table->increments('id');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Tear down the database schema.
     */
    protected function tearDown(): void
    {
        m::close();

        $this->schema()->drop('users');

        Container::setInstance(null);
    }

    /**
     * Get a database connection instance.
     *
     * @return \Illuminate\Database\ConnectionInterface
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

    public function testItGeneratesTheCorrectDataForCastableAttributes()
    {
        $user = User::factory()->create();

        $this->assertIsArray($user->array_column);
        $this->assertIsArray($user->json_column);
        $this->assertIsArray($user->object_column);
        $this->assertInstanceOf(Collection::class, $user->collection_column);
        $this->assertIsArray($user->encrypted_array_column);
        $this->assertInstanceOf(Collection::class, $user->encrypted_collection_column);
        $this->assertIsArray($user->encrypted_json_column);
        $this->assertIsArray($user->encrypted_object_column);
        $this->assertInstanceOf(ArrayObject::class, $user->as_array_object_column);
        $this->assertInstanceOf(Collection::class, $user->as_collection_column);
        $this->assertInstanceOf(ArrayObject::class, $user->as_encrypted_array_object_column);
        $this->assertInstanceOf(Collection::class, $user->as_encrypted_collection_column);

        $this->assertInstanceOf(Carbon::class, $user->datetime_column);
        $this->assertInstanceOf(Carbon::class, $user->date_column);
        $this->assertInstanceOf(CarbonImmutable::class, $user->immutable_datetime_column);
        $this->assertInstanceOf(CarbonImmutable::class, $user->immutable_date_column);
        $this->assertInstanceOf(Carbon::class, $user->datetime_custom_column);

        $this->assertTrue(is_int($user->integer_column));
        $this->assertTrue(is_float($user->float_column));
        $this->assertTrue(is_numeric($user->decimal_column));
        $this->assertTrue(is_bool($user->boolean_column));
        $this->assertTrue(is_int($user->timestamp_column));
        $this->assertTrue(is_string($user->string_column));
        $this->assertInstanceOf(FooBarEnum::class, $user->enum_column);
        $this->assertInstanceOf(Collection::class, $user->enum_collection_column);
        $this->assertInstanceOf(FooBarBackedEnum::class, $user->backed_enum_column);
        $this->assertInstanceOf(Collection::class, $user->backed_enum_collection_column);
    }

    public function testItGeneratesTheCorrectDataWhenGuessingValues()
    {
        $fake = m::mock(Generator::class);
        app()->singleton(Generator::class.':en_US', fn () => $fake);

        $fake->shouldReceive('safeEmail')->twice()->andReturn('joe@laravel.com');
        $fake->shouldReceive('name')->andReturn('Joe Dixon');
        $fake->shouldReceive('firstName')->andReturn('Joe');
        $fake->shouldReceive('lastName')->andReturn('Dixon');
        $fake->shouldReceive('username')->andReturn('_joedixon');
        $fake->shouldReceive('dateTime')->andReturn(now());

        $user = GuessUser::factory()->create();

        $this->assertSame('joe@laravel.com', $user->email);
        $this->assertSame('joe@laravel.com', $user->email_address);
        $this->assertSame('Joe Dixon', $user->name);
        $this->assertSame('Joe', $user->first_name);
        $this->assertSame('Dixon', $user->last_name);
        $this->assertSame('_joedixon', $user->username);
    }

    public function testItDoesNotGenerateForeignKeyValues()
    {
        $this->expectException(QueryException::class);
        $this->expectExceptionMessage('SQLSTATE[23000]: Integrity constraint violation: 19');

        Post::factory()->create();
    }
}

class User extends Eloquent
{
    use HasFactory;

    protected $table = 'users';

    protected $casts = [
        'array_column' => 'array',
        'json_column' => 'json',
        'object_column' => 'object',
        'collection_column' => 'collection',
        'encrypted_array_column' => 'encrypted:array',
        'encrypted_collection_column' => 'encrypted:collection',
        'encrypted_json_column' => 'encrypted:json',
        'encrypted_object_column' => 'encrypted:object',
        'as_array_object_column' => AsArrayObject::class,
        'as_collection_column' => AsCollection::class,
        'as_encrypted_array_object_column' => AsEncryptedArrayObject::class,
        'as_encrypted_collection_column' => AsEncryptedCollection::class,
        'datetime_column' => 'datetime',
        'date_column' => 'date',
        'immutable_datetime_column' => 'immutable_datetime',
        'immutable_date_column' => 'immutable_date',
        'datetime_custom_column' => 'datetime:Y-m-d',
        'integer_column' => 'integer',
        'float_column' => 'float',
        'double_column' => 'double',
        'decimal_column' => 'decimal:2',
        'boolean_column' => 'boolean',
        'timestamp_column' => 'timestamp',
        'string_column' => 'string',
        'enum_column' => FooBarEnum::class,
        'enum_collection_column' => AsEnumCollection::class.':'.FooBarEnum::class,
        'backed_enum_column' => FooBarBackedEnum::class,
        'backed_enum_collection_column' => AsEnumCollection::class.':'.FooBarBackedEnum::class,
    ];
}

class GuessUser extends Eloquent
{
    use HasFactory;

    protected $table = 'guess_users';
}

class Post extends Eloquent
{
    use HasFactory;

    protected $table = 'posts';
}

enum FooBarEnum
{
    case FOO;
    case BAR;
}

enum FooBarBackedEnum: string
{
    case FOO = 'foo';
    case BAR = 'bar';
}
