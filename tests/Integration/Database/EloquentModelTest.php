<?php

use Orchestra\Testbench\TestCase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Model;

/**
 * @group integration
 */
class EloquentModelTest extends TestCase
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
            $table->timestamp('nullable_date')->nullable();
        });
    }

    public function test_user_can_update_nullable_date()
    {
        $user = EloquentModelTestModel::create([
            'nullable_date' => null,
        ]);

        $user->fill([
            'nullable_date' => $now = \Illuminate\Support\Carbon::now(),
        ]);
        $this->assertTrue($user->isDirty('nullable_date'));

        $user->save();
        $this->assertEquals($now->toDateString(), $user->nullable_date->toDateString());
    }
}

class EloquentModelTestModel extends Model
{
    public $table = 'users';
    public $timestamps = false;
    protected $guarded = ['id'];
    protected $dates = ['nullable_date'];
}
