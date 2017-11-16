<?php

namespace Illuminate\Tests\Integration\Database;

use Orchestra\Testbench\TestCase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection as BaseCollection;

/**
 * @group integration
 */
class EloquentModelCollectionTest extends TestCase
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

        Schema::create('test_model', function ($table) {
            $table->increments('id');
        });
    }

    public function test_it_returns_a_different_collection_instance()
    {
        $this->assertNotInstanceOf(DummyCollection::class, TestModelBaseCollection::all());
        $this->assertInstanceOf(BaseCollection::class, TestModelBaseCollection::all());

        $collection = TestModel::all();

        $this->assertInstanceOf(DummyCollection::class, $collection);
        $this->assertInstanceOf(BaseCollection::class, $collection);
    }
}

class TestModel extends Model
{
    public $table = 'test_model';
    public $timestamps = false;
    protected $guarded = ['id'];
    protected $collection = DummyCollection::class;
}

class TestModelBaseCollection extends Model
{
    public $table = 'test_model';
    public $timestamps = false;
    protected $guarded = ['id'];
}

class DummyCollection extends BaseCollection
{

}