<?php

namespace Illuminate\Tests\Integration\Queue;

use Schema;
use LogicException;
use Orchestra\Testbench\TestCase;
use Illuminate\Queue\SerializesModels;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @group integration
 */
class ModelSerializationTest extends TestCase
{
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('app.debug', 'true');

        $app['config']->set('database.default', 'testbench');

        $app['config']->set('database.connections.testbench', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        $app['config']->set('database.connections.custom', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('email');
        });

        Schema::connection('custom')->create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('email');
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->increments('id');
        });

        Schema::create('lines', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('order_id');
            $table->unsignedInteger('product_id');
        });

        Schema::create('products', function (Blueprint $table) {
            $table->increments('id');
        });

        Schema::create('roles', function (Blueprint $table) {
            $table->increments('id');
        });

        Schema::create('role_user', function (Blueprint $table) {
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('role_id');
        });
    }

    public function test_it_serialize_user_on_default_connection()
    {
        $user = ModelSerializationTestUser::create([
            'email' => 'mohamed@laravel.com',
        ]);

        ModelSerializationTestUser::create([
            'email' => 'taylor@laravel.com',
        ]);

        $serialized = serialize(new ModelSerializationTestClass($user));

        $unSerialized = unserialize($serialized);

        $this->assertEquals('testbench', $unSerialized->user->getConnectionName());
        $this->assertEquals('mohamed@laravel.com', $unSerialized->user->email);

        $serialized = serialize(new ModelSerializationTestClass(ModelSerializationTestUser::on('testbench')->get()));

        $unSerialized = unserialize($serialized);

        $this->assertEquals('testbench', $unSerialized->user[0]->getConnectionName());
        $this->assertEquals('mohamed@laravel.com', $unSerialized->user[0]->email);
        $this->assertEquals('testbench', $unSerialized->user[1]->getConnectionName());
        $this->assertEquals('taylor@laravel.com', $unSerialized->user[1]->email);
    }

    public function test_it_serialize_user_on_different_connection()
    {
        $user = ModelSerializationTestUser::on('custom')->create([
            'email' => 'mohamed@laravel.com',
        ]);

        ModelSerializationTestUser::on('custom')->create([
            'email' => 'taylor@laravel.com',
        ]);

        $serialized = serialize(new ModelSerializationTestClass($user));

        $unSerialized = unserialize($serialized);

        $this->assertEquals('custom', $unSerialized->user->getConnectionName());
        $this->assertEquals('mohamed@laravel.com', $unSerialized->user->email);

        $serialized = serialize(new ModelSerializationTestClass(ModelSerializationTestUser::on('custom')->get()));

        $unSerialized = unserialize($serialized);

        $this->assertEquals('custom', $unSerialized->user[0]->getConnectionName());
        $this->assertEquals('mohamed@laravel.com', $unSerialized->user[0]->email);
        $this->assertEquals('custom', $unSerialized->user[1]->getConnectionName());
        $this->assertEquals('taylor@laravel.com', $unSerialized->user[1]->email);
    }

    public function test_it_fails_if_models_on_multi_connections()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Queueing collections with multiple model connections is not supported.');

        $user = ModelSerializationTestUser::on('custom')->create([
            'email' => 'mohamed@laravel.com',
        ]);

        $user2 = ModelSerializationTestUser::create([
            'email' => 'taylor@laravel.com',
        ]);

        $serialized = serialize(new ModelSerializationTestClass(
            new Collection([$user, $user2])
        ));

        unserialize($serialized);
    }

    public function test_it_reloads_relationships()
    {
        $order = tap(Order::create(), function (Order $order) {
            $order->wasRecentlyCreated = false;
        });

        $product1 = Product::create();
        $product2 = Product::create();

        Line::create(['order_id' => $order->id, 'product_id' => $product1->id]);
        Line::create(['order_id' => $order->id, 'product_id' => $product2->id]);

        $order->load('line', 'lines', 'products');

        $serialized = serialize(new ModelRelationSerializationTestClass($order));
        $unSerialized = unserialize($serialized);

        $this->assertEquals($unSerialized->order->getRelations(), $order->getRelations());
    }

    public function test_it_reloads_nested_relationships()
    {
        $order = tap(Order::create(), function (Order $order) {
            $order->wasRecentlyCreated = false;
        });

        $product1 = Product::create();
        $product2 = Product::create();

        Line::create(['order_id' => $order->id, 'product_id' => $product1->id]);
        Line::create(['order_id' => $order->id, 'product_id' => $product2->id]);

        $order->load('line.product', 'lines', 'lines.product', 'products');

        $nestedSerialized = serialize(new ModelRelationSerializationTestClass($order));
        $nestedUnSerialized = unserialize($nestedSerialized);

        $this->assertEquals($nestedUnSerialized->order->getRelations(), $order->getRelations());
    }

    /**
     * Regression test for https://github.com/laravel/framework/issues/23068.
     */
    public function test_it_can_unserialize_nested_relationships_without_pivot()
    {
        $user = tap(User::create([
            'email' => 'taylor@laravel.com',
        ]), function (User $user) {
            $user->wasRecentlyCreated = false;
        });

        $role1 = Role::create();
        $role2 = Role::create();

        RoleUser::create(['user_id' => $user->id, 'role_id' => $role1->id]);
        RoleUser::create(['user_id' => $user->id, 'role_id' => $role2->id]);

        $user->roles->each(function ($role) {
            $role->pivot->load('user', 'role');
        });

        $serialized = serialize(new ModelSerializationTestClass($user));
        unserialize($serialized);
    }

    public function test_it_serializes_an_empty_collection()
    {
        $serialized = serialize(new ModelSerializationTestClass(
            new Collection([])
        ));

        unserialize($serialized);
    }
}

class ModelSerializationTestUser extends Model
{
    public $table = 'users';
    public $guarded = ['id'];
    public $timestamps = false;
}

class Order extends Model
{
    public $guarded = ['id'];
    public $timestamps = false;

    public function line()
    {
        return $this->hasOne(Line::class);
    }

    public function lines()
    {
        return $this->hasMany(Line::class);
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'lines');
    }
}

class Line extends Model
{
    public $guarded = ['id'];
    public $timestamps = false;

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}

class Product extends Model
{
    public $guarded = ['id'];
    public $timestamps = false;
}

class User extends Model
{
    public $guarded = ['id'];
    public $timestamps = false;

    public function roles()
    {
        return $this->belongsToMany(Role::class)
            ->using(RoleUser::class);
    }
}

class Role extends Model
{
    public $guarded = ['id'];
    public $timestamps = false;

    public function users()
    {
        return $this->belongsToMany(User::class)
            ->using(RoleUser::class);
    }
}

class RoleUser extends Pivot
{
    public $guarded = ['id'];
    public $timestamps = false;

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }
}

class ModelSerializationTestClass
{
    use SerializesModels;

    public $user;

    public function __construct($user)
    {
        $this->user = $user;
    }
}

class ModelRelationSerializationTestClass
{
    use SerializesModels;

    public $order;

    public function __construct($order)
    {
        $this->order = $order;
    }
}
