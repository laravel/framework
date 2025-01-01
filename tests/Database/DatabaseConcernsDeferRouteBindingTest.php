<?php

declare(strict_types=1);

namespace Illuminate\Tests\Database;

use Carbon\Carbon;
use Faker\Generator;
use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Tests\Database\Fixtures\Factories\UserFactory;
use Illuminate\Tests\Database\Fixtures\Models\LazilyResolved;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class DatabaseConcernsDeferRouteBindingTest extends TestCase
{
    /**
     * Setup the database schema.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $container = Container::getInstance();
        $container->singleton(Generator::class, function ($app, $parameters) {
            return \Faker\Factory::create('en_US');
        });

        $db = new DB;

        $db->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);

        $db->bootEloquent();
        $db->setAsGlobal();

        $this->createSchema();
    }

    protected function createSchema()
    {
        $this->schema('default')->create('users', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Tear down the database schema.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        foreach (['default'] as $connection) {
            $this->schema($connection)->drop('users');
        }

        Relation::morphMap([], false);
        Eloquent::unsetConnectionResolver();

        \Illuminate\Support\Carbon::setTestNow(null);
    }

    public function testExplicitlyResolvesTheModel()
    {
        $user = UserFactory::new()->create();

        $model = new LazilyResolved;
        $lazy = $model->resolveRouteBinding($user->internal_id);

        $this->assertInstanceOf(LazilyResolved::class, $lazy);
        $this->assertTrue((new ReflectionClass($this->getProtectedProperty($lazy, 'deferredInit')))->isAnonymous());
        $this->assertFalse($this->getProtectedProperty($lazy, 'deferredInitResolved'));
        $this->assertCount(0, $this->getProtectedProperty($lazy, 'attributes'));

        $lazy();

        $this->assertNull($this->getProtectedProperty($lazy, 'deferredInit'));
        $this->assertTrue($this->getProtectedProperty($lazy, 'deferredInitResolved'));
        $this->assertEquals([
            'name' => $user->name,
            'email' => $user->email,
            'email_verified_at' => $user->getRawOriginal('email_verified_at'),
            'password' => $user->getRawOriginal('password'),
            'remember_token' => $user->getRawOriginal('remember_token'),
            'created_at' => $user->getRawOriginal('created_at'),
            'updated_at' => $user->getRawOriginal('updated_at'),
            'deleted_at' => null,
            'id' => 1,
        ], $this->getProtectedProperty($lazy, 'attributes'));
    }

    public function testImplicitlyResolvesTheModelOnPropertyAccess()
    {
        $user = UserFactory::new()->create();

        $model = new LazilyResolved;
        $lazy = $model->resolveRouteBinding($user->internal_id);

        $this->assertInstanceOf(LazilyResolved::class, $lazy);
        $this->assertTrue((new ReflectionClass($this->getProtectedProperty($lazy, 'deferredInit')))->isAnonymous());
        $this->assertFalse($this->getProtectedProperty($lazy, 'deferredInitResolved'));
        $this->assertCount(0, $this->getProtectedProperty($lazy, 'attributes'));

        $lazy->name;

        $this->assertNull($this->getProtectedProperty($lazy, 'deferredInit'));
        $this->assertTrue($this->getProtectedProperty($lazy, 'deferredInitResolved'));
        $this->assertEquals([
            'id' => $user->internal_id,
            'name' => $user->name,
            'email' => $user->email,
            'email_verified_at' => $user->getRawOriginal('email_verified_at'),
            'password' => $user->getRawOriginal('password'),
            'remember_token' => $user->getRawOriginal('remember_token'),
            'created_at' => $user->getRawOriginal('created_at'),
            'updated_at' => $user->getRawOriginal('updated_at'),
            'deleted_at' => null,
        ], $this->getProtectedProperty($lazy, 'attributes'));
    }

    public function testImplicitlyResolvesTheModelOnPropertyWrite()
    {
        $user = UserFactory::new()->create();

        $model = new LazilyResolved;
        $lazy = $model->resolveRouteBinding($user->internal_id);

        $this->assertInstanceOf(LazilyResolved::class, $lazy);
        $this->assertTrue((new ReflectionClass($this->getProtectedProperty($lazy, 'deferredInit')))->isAnonymous());
        $this->assertFalse($this->getProtectedProperty($lazy, 'deferredInitResolved'));
        $this->assertCount(0, $this->getProtectedProperty($lazy, 'attributes'));

        $lazy->name = 'Test User Changed';

        $this->assertNull($this->getProtectedProperty($lazy, 'deferredInit'));
        $this->assertTrue($this->getProtectedProperty($lazy, 'deferredInitResolved'));
        $this->assertEquals([
            'id' => $user->internal_id,
            'name' => 'Test User Changed',
            'email' => $user->email,
            'email_verified_at' => $user->getRawOriginal('email_verified_at'),
            'password' => $user->getRawOriginal('password'),
            'remember_token' => $user->getRawOriginal('remember_token'),
            'created_at' => $user->getRawOriginal('created_at'),
            'updated_at' => $user->getRawOriginal('updated_at'),
            'deleted_at' => null,
        ], $this->getProtectedProperty($lazy, 'attributes'));
    }

    public function testImplicitlyResolvesTheModelOnToArray()
    {
        $user = UserFactory::new()->create();

        $model = new LazilyResolved;
        $lazy = $model->resolveRouteBinding($user->internal_id);

        $this->assertInstanceOf(LazilyResolved::class, $lazy);
        $this->assertTrue((new ReflectionClass($this->getProtectedProperty($lazy, 'deferredInit')))->isAnonymous());
        $this->assertFalse($this->getProtectedProperty($lazy, 'deferredInitResolved'));
        $this->assertCount(0, $this->getProtectedProperty($lazy, 'attributes'));

        $array = $lazy->toArray();

        $this->assertNull($this->getProtectedProperty($lazy, 'deferredInit'));
        $this->assertTrue($this->getProtectedProperty($lazy, 'deferredInitResolved'));
        $this->assertEquals([
            'id' => $user->internal_id,
            'name' => $user->name,
            'email' => $user->email,
            'email_verified_at' => $user->getRawOriginal('email_verified_at'),
            'password' => $user->getRawOriginal('password'),
            'remember_token' => $user->getRawOriginal('remember_token'),
            'created_at' => $user->created_at->format('Y-m-d\TH:i:s.u\Z'),
            'updated_at' => $user->updated_at->format('Y-m-d\TH:i:s.u\Z'),
            'deleted_at' => null,
        ], $array);
    }

    public function testImplicitlyResolvesTheModelOnToJson()
    {
        $user = UserFactory::new()->create();

        $model = new LazilyResolved;
        $lazy = $model->resolveRouteBinding($user->internal_id);

        $this->assertInstanceOf(LazilyResolved::class, $lazy);
        $this->assertTrue((new ReflectionClass($this->getProtectedProperty($lazy, 'deferredInit')))->isAnonymous());
        $this->assertFalse($this->getProtectedProperty($lazy, 'deferredInitResolved'));
        $this->assertCount(0, $this->getProtectedProperty($lazy, 'attributes'));

        $json = $lazy->toJson();

        $this->assertNull($this->getProtectedProperty($lazy, 'deferredInit'));
        $this->assertTrue($this->getProtectedProperty($lazy, 'deferredInitResolved'));
        $this->assertEquals(json_encode([
            'id' => $user->internal_id,
            'name' => $user->name,
            'email' => $user->email,
            'email_verified_at' => $user->getRawOriginal('email_verified_at'),
            'password' => $user->getRawOriginal('password'),
            'remember_token' => $user->getRawOriginal('remember_token'),
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
            'deleted_at' => null,
        ]), $json);
    }

    public function testImplicitlyResolvesTheModelOnJsonEncode()
    {
        $user = UserFactory::new()->create();

        $model = new LazilyResolved;
        $lazy = $model->resolveRouteBinding($user->internal_id);

        $this->assertInstanceOf(LazilyResolved::class, $lazy);
        $this->assertTrue((new ReflectionClass($this->getProtectedProperty($lazy, 'deferredInit')))->isAnonymous());
        $this->assertFalse($this->getProtectedProperty($lazy, 'deferredInitResolved'));
        $this->assertCount(0, $this->getProtectedProperty($lazy, 'attributes'));

        $json = json_encode($lazy);

        $this->assertNull($this->getProtectedProperty($lazy, 'deferredInit'));
        $this->assertTrue($this->getProtectedProperty($lazy, 'deferredInitResolved'));
        $this->assertEquals(json_encode([
            'id' => $user->internal_id,
            'name' => $user->name,
            'email' => $user->email,
            'email_verified_at' => $user->getRawOriginal('email_verified_at'),
            'password' => $user->getRawOriginal('password'),
            'remember_token' => $user->getRawOriginal('remember_token'),
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
            'deleted_at' => null,
        ]), $json);
    }

    #[DataProvider('updateMethodsProvider')]
    public function testImplicitlyResolvesTheModelOnUpdate(string $method)
    {
        $user = UserFactory::new()->create();

        $model = new LazilyResolved;
        $lazy = $model->resolveRouteBinding($user->internal_id);

        $this->assertInstanceOf(LazilyResolved::class, $lazy);
        $this->assertTrue((new ReflectionClass($this->getProtectedProperty($lazy, 'deferredInit')))->isAnonymous());
        $this->assertFalse($this->getProtectedProperty($lazy, 'deferredInitResolved'));
        $this->assertCount(0, $this->getProtectedProperty($lazy, 'attributes'));

        Carbon::setTestNow(now()->addSecond());

        $lazy->{$method}([
            'name' => 'Test User Updated',
            'email' => 'davey@php.net',
        ]);

        $this->assertNull($this->getProtectedProperty($lazy, 'deferredInit'));
        $this->assertTrue($this->getProtectedProperty($lazy, 'deferredInitResolved'));
        $this->assertEquals([
            'id' => $user->internal_id,
            'name' => 'Test User Updated',
            'email' => 'davey@php.net',
            'email_verified_at' => $user->getRawOriginal('email_verified_at'),
            'password' => $user->getRawOriginal('password'),
            'remember_token' => $user->getRawOriginal('remember_token'),
            'created_at' => $user->getRawOriginal('created_at'),
            'updated_at' => now()->format('Y-m-d H:i:s'),
            'deleted_at' => null,
        ], $this->getProtectedProperty($lazy, 'attributes'));
    }

    public static function updateMethodsProvider(): array
    {
        return [
            ['update'],
            ['updateQuietly'],
            ['updateOrFail'],
        ];
    }

    #[DataProvider('deleteMethodsProvider')]
    public function testImplicitlyResolvesTheModelOnDelete(string $method)
    {
        $user = UserFactory::new()->create();

        $model = new LazilyResolved;
        $lazy = $model->resolveRouteBinding($user->internal_id);

        $this->assertInstanceOf(LazilyResolved::class, $lazy);
        $this->assertTrue((new ReflectionClass($this->getProtectedProperty($lazy, 'deferredInit')))->isAnonymous());
        $this->assertFalse($this->getProtectedProperty($lazy, 'deferredInitResolved'));
        $this->assertCount(0, $this->getProtectedProperty($lazy, 'attributes'));

        $lazy->{$method}();

        $this->assertNull($this->getProtectedProperty($lazy, 'deferredInit'));
        $this->assertTrue($this->getProtectedProperty($lazy, 'deferredInitResolved'));
        $this->assertEquals([
            'id' => $user->internal_id,
            'name' => $user->getRawOriginal('name'),
            'email' => $user->getRawOriginal('email'),
            'email_verified_at' => $user->getRawOriginal('email_verified_at'),
            'password' => $user->getRawOriginal('password'),
            'remember_token' => $user->getRawOriginal('remember_token'),
            'created_at' => $user->getRawOriginal('created_at'),
            'updated_at' => now()->format('Y-m-d H:i:s'),
            'deleted_at' => now()->format('Y-m-d H:i:s'),
        ], $this->getProtectedProperty($lazy, 'attributes'));
        $this->assertEquals(0, LazilyResolved::count());
    }

    public static function deleteMethodsProvider(): array
    {
        return [
            ['delete'],
            ['deleteQuietly'],
            ['deleteOrFail'],
        ];
    }

    public function testImplicitlyResolvesTheModelOnToString()
    {
        $user = UserFactory::new()->create();

        $model = new LazilyResolved;
        $lazy = $model->resolveRouteBinding($user->internal_id);

        $this->assertInstanceOf(LazilyResolved::class, $lazy);
        $this->assertTrue((new ReflectionClass($this->getProtectedProperty($lazy, 'deferredInit')))->isAnonymous());
        $this->assertFalse($this->getProtectedProperty($lazy, 'deferredInitResolved'));
        $this->assertCount(0, $this->getProtectedProperty($lazy, 'attributes'));

        (string) $lazy;

        $this->assertNull($this->getProtectedProperty($lazy, 'deferredInit'));
        $this->assertTrue($this->getProtectedProperty($lazy, 'deferredInitResolved'));
        $this->assertEquals([
            'id' => $user->internal_id,
            'name' => $user->getRawOriginal('name'),
            'email' => $user->getRawOriginal('email'),
            'email_verified_at' => $user->getRawOriginal('email_verified_at'),
            'password' => $user->getRawOriginal('password'),
            'remember_token' => $user->getRawOriginal('remember_token'),
            'created_at' => $user->getRawOriginal('created_at'),
            'updated_at' => now()->format('Y-m-d H:i:s'),
            'deleted_at' => null,
        ], $this->getProtectedProperty($lazy, 'attributes'));
    }

    /**
     * Helpers...
     */

    /**
     * Access a protected property of an object.
     *
     * @return mixed
     */
    protected function getProtectedProperty(object $object, string $property)
    {
        $closure = function () use ($property) {
            return $this->{$property};
        };

        return $closure->bindTo($object, $object)();
    }

    /**
     * Get a database connection instance.
     *
     * @return \Illuminate\Database\Connection
     */
    protected function connection($connection = 'default')
    {
        return Eloquent::getConnectionResolver()->connection($connection);
    }

    /**
     * Get a schema builder instance.
     *
     * @return \Illuminate\Database\Schema\Builder
     */
    protected function schema($connection = 'default')
    {
        return $this->connection($connection)->getSchemaBuilder();
    }
}
