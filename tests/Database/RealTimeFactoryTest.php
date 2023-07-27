<?php

namespace Illuminate\Tests\Database;

use ArrayObject;
use Carbon\CarbonImmutable;
use DateTime;
use Faker\Generator;
use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Casts\AsEncryptedArrayObject;
use Illuminate\Database\Eloquent\Casts\AsEncryptedCollection;
use Illuminate\Database\Eloquent\Casts\AsEnumCollection;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasRealTimeFactory;
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
        Cast::encryptUsing($encrypter = new Encrypter(Str::random()));
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
        $this->schema()->create('casts', function ($table) {
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

        $this->schema()->create('types', function ($table) {
            $table->bigIncrements('big_increments_column');
            $table->bigInteger('big_integer_column');
            $table->binary('binary_column');
            $table->boolean('boolean_column');
            $table->char('char_column');
            $table->dateTimeTz('date_time_tz_column');
            $table->dateTime('date_time_column');
            $table->date('date_column');
            $table->decimal('decimal_column');
            $table->double('double_column');
            $table->float('float_column');
            $table->foreignId('foreign_id_column');
            $table->foreignIdFor(Cast::class);
            $table->foreignUlid('foreign_ulid_column');
            $table->foreignUuid('foreign_uuid_column');
            $table->geometry('geometry_column');
            $table->integer('integer_column');
            $table->ipAddress('ip_address_column');
            $table->json('json_column');
            $table->jsonb('jsonb_column');
            $table->lineString('line_string_column');
            $table->longText('long_text_column');
            $table->macAddress('mac_address_column');
            $table->mediumInteger('medium_integer_column');
            $table->mediumText('medium_text_column');
            $table->morphs('morphs_column');
            $table->multiLineString('multi_line_string_column');
            $table->multiPoint('multi_point_column');
            $table->multiPolygon('multi_polygon_column');
            $table->nullableMorphs('nullable_morphs_column');
            $table->nullableUlidMorphs('nullable_ulid_morphs_column');
            $table->nullableUuidMorphs('nullable_uuid_morphs_column');
            $table->point('point_column');
            $table->polygon('polygon_column');
            $table->rememberToken('remember_token_column');
            $table->smallInteger('small_integer_column');
            $table->softDeletesTz('soft_deletes_tz_column');
            $table->softDeletes('soft_deletes_column');
            $table->string('string_column');
            $table->text('text_column');
            $table->timeTz('time_tz_column');
            $table->time('time_column');
            $table->timestampTz('timestamp_tz_column');
            $table->timestamp('timestamp_column');
            $table->tinyInteger('tiny_integer_column');
            $table->tinyText('tiny_text_column');
            $table->unsignedBigInteger('unsigned_big_integer_column');
            $table->unsignedDecimal('unsigned_decimal_column');
            $table->unsignedInteger('unsigned_integer_column');
            $table->unsignedMediumInteger('unsigned_medium_integer_column');
            $table->unsignedSmallInteger('unsigned_small_integer_column');
            $table->unsignedTinyInteger('unsigned_tiny_integer_column');
            $table->ulidMorphs('ulid_morphs_column');
            $table->uuidMorphs('uuid_morphs_column');
            $table->ulid('ulid_column');
            $table->uuid('uuid_column');
            $table->year('year_column');
            $table->timestamps();
        });

        $this->schema()->create('nullables', function ($table) {
            $table->string('string_column');
            $table->string('nullable_string_column')->nullable();
            $table->string('default_null_string_column')->nullable()->default(null);
            $table->string('default_value_string_column')->nullable()->default('Joe');
            $table->timestamps();
        });

        $this->schema()->create('guesses', function ($table) {
            $table->increments('id');
            $table->string('email');
            $table->string('email_address');
            $table->string('name');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('username');
            $table->timestamps();
        });

        $this->schema()->create('keys', function ($table) {
            $table->increments('id');
            $table->foreignId('cast_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });

        $this->schema()->create('users', function ($table) {
            $table->increments('id');
            $table->string('name');
            $table->string('email');
            $table->timestamps();
        });

        $this->schema()->create('posts', function ($table) {
            $table->increments('id');
            $table->foreignIdFor(User::class)->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('body');
            $table->boolean('published');
            $table->timestamps();
        });

        $this->schema()->create('roles', function ($table) {
            $table->increments('id');
            $table->string('name');
            $table->timestamps();
        });

        $this->schema()->create('role_user', function ($table) {
            $table->increments('id');
            $table->foreignIdFor(User::class)->constrained()->onDelete('cascade');
            $table->foreignIdFor(Role::class)->constrained()->onDelete('cascade');
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        $this->schema()->create('comments', function ($table) {
            $table->increments('id');
            $table->morphs('commentable');
            $table->string('body');
            $table->timestamps();
        });

        $this->schema()->create('tags', function ($table) {
            $table->increments('id');
            $table->string('name');
            $table->timestamps();
        });

        $this->schema()->create('taggables', function ($table) {
            $table->increments('id');
            $table->foreignIdFor(Tag::class)->constrained();
            $table->morphs('taggable');
            $table->boolean('public')->default(false);
            $table->timestamps();
        });

        $this->schema()->create('reserved_words', function ($table) {
            $table->increments('id');
            $table->string('key');
            $table->timestamps();
        });
    }

    /**
     * Tear down the database schema.
     */
    protected function tearDown(): void
    {
        m::close();

        $this->schema()->drop('casts');
        $this->schema()->drop('types');
        $this->schema()->drop('nullables');
        $this->schema()->drop('guesses');
        $this->schema()->drop('keys');
        $this->schema()->drop('users');
        $this->schema()->drop('posts');
        $this->schema()->drop('roles');
        $this->schema()->drop('role_user');
        $this->schema()->drop('comments');
        $this->schema()->drop('tags');
        $this->schema()->drop('taggables');
        $this->schema()->drop('reserved_words');

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
        $cast = Cast::factory()->create();

        $this->assertIsArray($cast->array_column);
        $this->assertIsArray($cast->json_column);
        $this->assertIsArray($cast->object_column);
        $this->assertInstanceOf(Collection::class, $cast->collection_column);
        $this->assertIsArray($cast->encrypted_array_column);
        $this->assertInstanceOf(Collection::class, $cast->encrypted_collection_column);
        $this->assertIsArray($cast->encrypted_json_column);
        $this->assertIsArray($cast->encrypted_object_column);
        $this->assertInstanceOf(ArrayObject::class, $cast->as_array_object_column);
        $this->assertInstanceOf(Collection::class, $cast->as_collection_column);
        $this->assertInstanceOf(ArrayObject::class, $cast->as_encrypted_array_object_column);
        $this->assertInstanceOf(Collection::class, $cast->as_encrypted_collection_column);

        $this->assertInstanceOf(Carbon::class, $cast->datetime_column);
        $this->assertInstanceOf(Carbon::class, $cast->date_column);
        $this->assertInstanceOf(CarbonImmutable::class, $cast->immutable_datetime_column);
        $this->assertInstanceOf(CarbonImmutable::class, $cast->immutable_date_column);
        $this->assertInstanceOf(Carbon::class, $cast->datetime_custom_column);

        $this->assertTrue(is_int($cast->integer_column));
        $this->assertTrue(is_float($cast->float_column));
        $this->assertTrue(is_numeric($cast->decimal_column));
        $this->assertTrue(is_bool($cast->boolean_column));
        $this->assertTrue(is_int($cast->timestamp_column));
        $this->assertTrue(is_string($cast->string_column));
        $this->assertInstanceOf(FooBarEnum::class, $cast->enum_column);
        $this->assertInstanceOf(Collection::class, $cast->enum_collection_column);
        $this->assertInstanceOf(FooBarBackedEnum::class, $cast->backed_enum_column);
        $this->assertInstanceOf(Collection::class, $cast->backed_enum_collection_column);
    }

    public function testItGeneratesTheCorrectDataForDbalTypes()
    {
        $type = Type::factory()->create();

        $this->assertTrue(collect([
            $type->big_increments_column,
            $type->nullable_morphs_column_type,
            $type->nullable_morphs_column_id,
            $type->nullable_ulid_morphs_column_type,
            $type->nullable_ulid_morphs_column_id,
            $type->nullable_uuid_morphs_column_type,
            $type->nullable_uuid_morphs_column_id,
            $type->remember_token,
            $type->soft_deletes_tz_column,
            $type->soft_deletes_column,
        ])->every(fn ($value) => is_null($value)));

        $this->assertTrue(collect([
            $type->char_column,
            $type->foreign_ulid_column,
            $type->foreign_uuid_column,
            $type->geometry_column,
            $type->ip_address_column,
            $type->line_string_column,
            $type->mac_address_column,
            $type->morphs_column_type,
            $type->multi_line_string_column,
            $type->multi_point_column,
            $type->multi_polygon_column,
            $type->point_column,
            $type->polygon_column,
            $type->string_column,
            $type->ulid_morphs_column_type,
            $type->ulid_morphs_column_id,
            $type->uuid_morphs_column_type,
            $type->uuid_morphs_column_id,
            $type->ulid_column,
            $type->uuid_column,
        ])->every(fn ($value) => is_string($value) && strlen($value) <= 10));

        $this->assertTrue(collect([
            $type->json_column,
            $type->jsonb_column,
            $type->long_text_column,
            $type->medium_text_column,
            $type->text_column,
            $type->tiny_text_column,
        ])->every(fn ($value) => is_string($value) && count(explode(' ', $value)) > 1));

        $this->assertTrue(collect([
            $type->big_integer_column,
            $type->foreign_id_column,
            $type->cast_id,
            $type->integer_column,
            $type->medium_integer_column,
            $type->morphs_column_id,
            $type->small_integer_column,
            $type->tiny_integer_column,
            $type->unsigned_big_integer_column,
            $type->unsigned_integer_column,
            $type->unsigned_medium_integer_column,
            $type->unsigned_small_integer_column,
            $type->unsigned_tiny_integer_column,
            $type->year_column,
        ])->every(fn ($value) => is_int($value)));

        $this->assertTrue(collect([
            $type->decimal_column,
            $type->double_column,
            $type->float_column,
            $type->unsigned_decimal_column,
        ])->every(fn ($value) => is_float($value)));

        $this->assertTrue(collect([
            $type->date_time_tz_column,
            $type->date_time_column,
            $type->timestamp_tz_column,
            $type->timestamp_column,
            $type->created_at,
            $type->updated_at,
        ])->every(fn ($value) => $value instanceof DateTime));

        $this->assertTrue(collect([
            $type->time_tz_column,
            $type->time_column,
        ])->every(fn ($value) => preg_match('/\d{2}:\d{2}:\d{2}/', $value)));
    }

    public function testItCorrectlyUsesDefaultColumnValues()
    {
        $nullable = Nullable::factory()->create();

        $this->assertNotNull($nullable->string_column);
        $this->assertNull($nullable->nullable_string_column);
        $this->assertNull($nullable->default_null_string_column);
        $this->assertSame('Joe', $nullable->default_value_string_column);
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

        $guess = Guess::factory()->create();

        $this->assertSame('joe@laravel.com', $guess->email);
        $this->assertSame('joe@laravel.com', $guess->email_address);
        $this->assertSame('Joe Dixon', $guess->name);
        $this->assertSame('Joe', $guess->first_name);
        $this->assertSame('Dixon', $guess->last_name);
        $this->assertSame('_joedixon', $guess->username);
    }

    public function testItDoesNotGenerateForeignKeyValues()
    {
        $this->expectException(QueryException::class);
        $this->expectExceptionMessage('SQLSTATE[23000]: Integrity constraint violation: 19');

        Key::factory()->create();
    }

    public function testItCanHandleHasManyRelationships()
    {
        $user = User::factory()->has(Post::factory()->count(3))->create();
        $this->assertInstanceOf(User::class, $user);
        $this->assertCount(3, $user->posts);

        $user = User::factory()->hasPosts(2)->create();
        $this->assertInstanceOf(User::class, $user);
        $this->assertCount(2, $user->posts);

        $user = User::factory()
            ->has(
                Post::factory()
                    ->count(3)
                    ->state(function (array $attributes, User $user) {
                        return ['title' => $user->name];
                    })
            )
            ->create(['name' => 'Joe Dixon']);
        $user->posts->each(fn ($post) => $this->assertSame('Joe Dixon', $post->title));

        $user = User::factory()
            ->hasPosts(3, [
                'published' => false,
            ])
            ->create();

        $user->posts->each(fn ($post) => $this->assertFalse($post->published));
    }

    public function testItCanHandleBelongsToRelationships()
    {
        $posts = Post::factory()
            ->count(3)
            ->for(User::factory()->state([
                'name' => 'Joe Dixon',
            ]))
            ->create();

        $this->assertCount(3, $posts);
        $this->assertCount(1, User::all());
        $posts->each(fn ($post) => $this->assertSame('Joe Dixon', $post->user->name));

        $user = User::factory()->create();

        $posts = Post::factory()
            ->count(2)
            ->for($user)
            ->create();
        $this->assertCount(2, $posts);

        $posts = Post::factory()
            ->count(3)
            ->forUser([
                'name' => 'Joe Dixon',
            ])
            ->create();
        $this->assertCount(3, $posts);
        $this->assertCount(1, $posts->pluck('user')->pluck('id')->unique());
    }

    public function testItCanHandleManyToManyRelationships()
    {
        $user = User::factory()
            ->has(Role::factory()->count(3))
            ->create();
        $this->assertCount(3, $user->roles);

        $user = User::factory()
            ->hasAttached(
                Role::factory()->count(3),
                ['active' => false]
            )
            ->create();
        $this->assertTrue($user->roles->every(fn ($role) => $role->pivot->active === 0));

        $user = User::factory()
            ->hasAttached(
                Role::factory()
                    ->count(3)
                    ->state(function (array $attributes, User $user) {
                        return ['name' => $user->name.' Role'];
                    }),
                ['active' => true]
            )
            ->create();
        $this->assertTrue($user->roles->every(fn ($role) => $role->name === $user->name.' Role'));

        $roles = Role::factory()->count(3)->create();
        $users = User::factory()
            ->count(3)
            ->hasAttached($roles, ['active' => false])
            ->create();
        $this->assertTrue($users->every(fn ($user) => $user->roles->every(fn ($role) => $role->pivot->active === 0)));

        $user = User::factory()
            ->hasRoles(1, [
                'name' => 'Editor',
            ])
            ->create();
        $this->assertCount(1, $user->roles);
        $this->assertSame('Editor', $user->roles->first()->name);
    }

    public function testItCanHandleMorphManyRelationships()
    {
        $post = Post::factory()->hasComments(3)->forUser()->create();

        $this->assertCount(3, $post->comments);
    }

    public function testItCanHandleMorphToRelationships()
    {
        $comments = Comment::factory()->count(3)->for(Post::factory()->forUser(), 'commentable')->create();

        $this->assertCount(3, $comments);
        $this->assertCount(1, $comments->pluck('commentable')->pluck('id')->unique());
        $this->assertCount(1, $comments->pluck('commentable')->pluck('user')->pluck('id')->unique());
    }

    public function testItCanHandleManyToManyPolymorphicRelationships()
    {
        $post = Post::factory()
            ->hasAttached(
                Tag::factory()->count(3),
                ['public' => true]
            )
            ->forUser()
            ->create();
        $this->assertTrue($post->tags->every(fn ($tag) => $tag->pivot->public === 1));

        $post = Post::factory()
            ->hasTags(3, ['name' => 'My Tag'])
            ->forUser()
            ->create();
        $this->assertCount(3, $post->tags);
        $this->assertTrue($post->tags->every(fn ($tag) => $tag->name === 'My Tag'));
    }

    public function testItCanBeUsedToDefineARelationWithinAFactory()
    {
        $post = PostFactory::new()->create();

        $this->assertNotNull($post->user);
    }

    public function testItCanKeyReservedColumnNames()
    {
        $word = ReservedWord::factory()->create();

        $this->assertContains('key', array_keys($word->toArray()));
    }
}

class Cast extends Eloquent
{
    use HasRealTimeFactory;

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

class Type extends Eloquent
{
    use HasRealTimeFactory;
}

class Nullable extends Eloquent
{
    use HasRealTimeFactory;
}

class Guess extends Eloquent
{
    use HasRealTimeFactory;
}

class Key extends Eloquent
{
    use HasRealTimeFactory;
}

class User extends Eloquent
{
    use HasRealTimeFactory;

    public function posts()
    {
        return $this->hasMany(Post::class);
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class)
            ->withPivot('active')
            ->withTimestamps();
    }
}

class Post extends Eloquent
{
    use HasRealTimeFactory;

    protected $casts = [
        'published' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function comments()
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    public function tags()
    {
        return $this->morphToMany(Tag::class, 'taggable')
            ->withPivot('public')
            ->withTimestamps();
    }
}

class Role extends Eloquent
{
    use HasRealTimeFactory;

    public function users()
    {
        return $this->belongsToMany(User::class)
            ->withPivot('active')
            ->withTimestamps();
    }
}

class Comment extends Eloquent
{
    use HasRealTimeFactory;

    public function commentable()
    {
        return $this->morphTo();
    }
}

class Tag extends Eloquent
{
    use HasRealTimeFactory;

    public function posts()
    {
        return $this->morphedByMany(Post::class, 'taggable');
    }
}

class ReservedWord extends Eloquent
{
    use HasRealTimeFactory;
}

class PostFactory extends Factory
{
    protected $model = Post::class;

    public function definition()
    {
        return [
            'user_id' => User::factory(),
            'title' => fake()->word(),
            'body' => fake()->paragraph(),
            'published' => fake()->boolean(),
        ];
    }
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
