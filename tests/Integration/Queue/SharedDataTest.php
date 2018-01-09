<?php

namespace Illuminate\Tests\Integration\Queue;

use Schema;
use Illuminate\Queue\SharedData;
use Orchestra\Testbench\TestCase;
use Illuminate\Database\Eloquent\Model;

/**
 * @group integration
 */
class SharedDataTest extends TestCase
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
    }

    public function setUp()
    {
        parent::setUp();

        Schema::create('users', function ($table) {
            $table->increments('id');
            $table->string('email');
        });
    }

    /**
     * @test
     */
    public function it_serialize_models_and_extra_data()
    {
        $user = ModelSerializationTestUserSharedData::create([
            'email' => 'taylor@laravel.com',
        ]);

        $array = ['foo' => 'bar', 'bar' => ['salt' => 'pepper', 'model' => $user]];

        $c = new SharedData($array);
        $serialized = serialize($c);
        $this->assertStringStartsWith('C:27:"Illuminate\Queue\SharedData"', $serialized);

        $d = unserialize($serialized);
        $this->assertInstanceOf(\Illuminate\Queue\SharedData::class, $d);
        $this->assertInstanceOf(ModelSerializationTestUserSharedData::class, $d->get('bar.model'));
        $this->assertSame('taylor@laravel.com', $d->get('bar.model')->email);
        $this->assertSame('bar', $d->get('foo'));
    }
}

class ModelSerializationTestUserSharedData extends Model
{
    public $table = 'users';
    public $guarded = ['id'];
    public $timestamps = false;
}
