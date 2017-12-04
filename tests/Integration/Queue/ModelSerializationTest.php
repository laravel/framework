<?php

namespace Illuminate\Tests\Integration\Queue;

use Schema;
use Orchestra\Testbench\TestCase;
use Illuminate\Database\Eloquent\Model;

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

    public function setUp()
    {
        parent::setUp();

        Schema::create('users', function ($table) {
            $table->increments('id');
            $table->string('email');
        });

        Schema::connection('custom')->create('users', function ($table) {
            $table->increments('id');
            $table->string('email');
        });
    }

    /**
     * @test
     */
    public function it_serialize_user_on_default_connection()
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

    /**
     * @test
     */
    public function it_serialize_user_on_different_connection()
    {
        $user = ModelSerializationTestUser::on('custom')->create([
            'email' => 'mohamed@laravel.com',
        ]);

        $user2 = ModelSerializationTestUser::on('custom')->create([
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

    /**
     * @test
     * @expectedException \LogicException
     * @expectedExceptionMessage  Queueing collections with multiple model connections is not supported.
     */
    public function it_fails_if_models_on_multi_connections()
    {
        $user = ModelSerializationTestUser::on('custom')->create([
            'email' => 'mohamed@laravel.com',
        ]);

        $user2 = ModelSerializationTestUser::create([
            'email' => 'taylor@laravel.com',
        ]);

        $serialized = serialize(new ModelSerializationTestClass(
            new \Illuminate\Database\Eloquent\Collection([$user, $user2])
        ));

        unserialize($serialized);
    }

    /** @test */
    public function it_serializes_an_empty_collection()
    {
        $serialized = serialize(new ModelSerializationTestClass(
            new \Illuminate\Database\Eloquent\Collection([])
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

class ModelSerializationTestClass
{
    use \Illuminate\Queue\SerializesModels;

    public $user;

    public function __construct($user)
    {
        $this->user = $user;
    }
}
