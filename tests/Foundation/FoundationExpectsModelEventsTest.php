<?php

use Illuminate\Events\Dispatcher;
use Mockery\MockInterface as Mock;
use Illuminate\Foundation\Application;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Model as Eloquent;

class FoundationExpectsModelEventsTest extends TestCase
{
    public function createApplication()
    {
        Model::clearBootedModels();

        $app = new Application;

        $db = new DB;

        $db->addConnection([
            'driver'    => 'sqlite',
            'database'  => ':memory:',
        ]);

        $db->bootEloquent();
        $db->setAsGlobal();

        $this->createSchema();

        return $app;
    }

    /** @test */
    public function a_mock_replaces_the_event_dispatcher_when_calling_expects_model_events()
    {
        $this->assertInstanceOf(Dispatcher::class, Model::getEventDispatcher());

        $this->assertNotInstanceOf(Mock::class, Model::getEventDispatcher());

        $this->expectsModelEvents(EloquentTestModel::class, []);

        $this->assertNotInstanceOf(Dispatcher::class, Model::getEventDispatcher());
        $this->assertInstanceOf(Mock::class, Model::getEventDispatcher());
    }

    /** @test */
    public function a_mock_does_not_carry_over_between_tests()
    {
        $this->assertInstanceOf(Dispatcher::class, Model::getEventDispatcher());

        $this->assertNotInstanceOf(Mock::class, Model::getEventDispatcher());
    }

    /** @test */
    public function fired_events_can_be_checked_for()
    {
        $this->expectsModelEvents(EloquentTestModel::class, [
            'booting',
            'booted',

            'creating',
            'created',

            'saving',
            'saved',

            'updating',
            'updated',

            'deleting',
            'deleted',
        ]);

        $model = EloquentTestModel::create(['field' => 1]);
        $model->field = 2;
        $model->save();
        $model->delete();
    }

    /** @test */
    public function using_expects_model_events_multiple_times_works()
    {
        $this->expectsModelEvents(EloquentTestModel::class, [
            'booting',
            'booted',
        ]);

        $this->expectsModelEvents(EloquentTestModel::class, [
            'creating',
            'created',
        ]);

        $model = EloquentTestModel::create(['field' => 1]);
        $model->field = 2;
        $model->save();
        $model->delete();
    }

    /** @test */
    public function expects_model_events_can_take_a_string_as_the_event_name()
    {
        $this->expectsModelEvents(EloquentTestModel::class, 'booting');

        EloquentTestModel::create(['field' => 1]);
    }

    /** @test */
    public function unfired_events_can_be_checked_for()
    {
        $this->doesntExpectModelEvents(EloquentTestModel::class, [
            'updating',
            'updated',

            'deleting',
            'deleted',
        ]);

        EloquentTestModel::create(['field' => 1]);
    }

    /** @test */
    public function using_doesnt_expect_model_events_multiple_times_works()
    {
        $this->doesntExpectModelEvents(EloquentTestModel::class, [
            'updating',
            'updated',
        ]);

        $this->doesntExpectModelEvents(EloquentTestModel::class, [
            'deleting',
            'deleted',
        ]);

        EloquentTestModel::create(['field' => 1]);
    }

    /** @test */
    public function doesnt_expect_model_events_can_take_a_string_as_the_event_name()
    {
        $this->doesntExpectModelEvents(EloquentTestModel::class, 'deleting');

        EloquentTestModel::create(['field' => 1]);
    }

    /** @test */
    public function observers_do_not_fire_when_mocking_events()
    {
        $this->expectsModelEvents(EloquentTestModel::class, [
            'saving',
            'saved',
        ]);

        $this->doesntExpectModelEvents(EloquentTestModel::class, [
            'deleting',
            'deleted',
        ]);

        EloquentTestModel::observe(new EloquentTestModelFailingObserver);

        EloquentTestModel::create(['field' => 1]);
    }

    protected function createSchema()
    {
        $this->schema('default')->create('test', function ($table) {
            $table->increments('id');
            $table->string('field');
            $table->timestamps();
        });
    }

    /**
     * Get a database connection instance.
     *
     * @return Connection
     */
    protected function connection($connection = 'default')
    {
        return Eloquent::getConnectionResolver()->connection($connection);
    }

    /**
     * Get a schema builder instance.
     *
     * @return Schema\Builder
     */
    protected function schema($connection = 'default')
    {
        return $this->connection($connection)->getSchemaBuilder();
    }
}

class EloquentTestModel extends Eloquent
{
    protected $guarded = [];
    protected $table = 'test';
}

class EloquentTestModelFailingObserver
{
    public function saving()
    {
        PHPUnit_Framework_Assert::fail('The [saving] method should not be called on '.static::class);
    }

    public function saved()
    {
        PHPUnit_Framework_Assert::fail('The [saved] method should not be called on '.static::class);
    }

    public function deleting()
    {
        PHPUnit_Framework_Assert::fail('The [deleting] method should not be called on '.static::class);
    }

    public function deleted()
    {
        PHPUnit_Framework_Assert::fail('The [deleted] method should not be called on '.static::class);
    }
}
