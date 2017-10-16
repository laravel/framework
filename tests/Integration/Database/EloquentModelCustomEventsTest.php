<?php

namespace Illuminate\Tests\Integration\Database\EloquentModelCustomEventsTest;

use Orchestra\Testbench\TestCase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Model;

/**
 * @group integration
 */
class EloquentModelCustomEventsTest extends TestCase
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

        Schema::create('test_model1', function ($table) {
            $table->increments('id');
        });

        Event::listen(CustomEvent::class, function () {
            $_SERVER['fired_event'] = true;
        });
    }

    public function testFlushListenersClearsCustomEvents()
    {
        $_SERVER['fired_event'] = false;

        TestModel1::flushEventListeners();

        $user = TestModel1::create();

        $this->assertFalse($_SERVER['fired_event']);
    }

    public function testCustomEventListenersAreFired()
    {
        $_SERVER['fired_event'] = false;

        $user = TestModel1::create();

        $this->assertTrue($_SERVER['fired_event']);
    }
}

class TestModel1 extends Model
{
    public $dispatchesEvents = ['created' => CustomEvent::class];
    public $table = 'test_model1';
    public $timestamps = false;
    protected $guarded = ['id'];
}

class CustomEvent
{
}
