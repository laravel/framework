<?php

namespace Illuminate\Tests\Integration\Database\EloquentModelCustomEventsTest;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Tests\Integration\Database\DatabaseTestCase;

/**
 * @group integration
 */
class EloquentModelCustomEventsTest extends DatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('test_model1', function (Blueprint $table) {
            $table->increments('id');
        });

        Event::listen(CustomCreatedEvent::class, function () {
            $_SERVER['fired_created_event'] = true;
        });

        Event::listen(CustomUpdatedEvent::class, function () {
            $_SERVER['fired_updated_event'] = true;
        });
    }

    public function testFlushListenersClearsCustomEvents()
    {
        $_SERVER['fired_created_event'] = false;
        $_SERVER['fired_updated_event'] = false;

        TestModel1::flushEventListeners();

        $model = TestModel1::create();
        $model->update();

        $this->assertFalse($_SERVER['fired_created_event']);
        $this->assertFalse($_SERVER['fired_updated_event']);
    }


    public function testFlushListenersClearsCustomEventsWithName()
    {
        $_SERVER['fired_created_event'] = false;
        $_SERVER['fired_updated_event'] = false;

        TestModel1::flushEventListeners([CustomCreatedEvent::class]);

        $model = TestModel1::create();
        $model->increment('id');

        $this->assertFalse($_SERVER['fired_created_event']);
        $this->assertTrue($_SERVER['fired_updated_event']);
    }

    public function testCustomEventListenersAreFired()
    {
        $_SERVER['fired_created_event'] = false;
        $_SERVER['fired_updated_event'] = false;

        TestModel1::flushEventListeners([CustomUpdatedEvent::class]);

        $model = TestModel1::create();
        $model->increment('id');

        $this->assertTrue($_SERVER['fired_created_event']);
        $this->assertFalse($_SERVER['fired_updated_event']);
    }
}

class TestModel1 extends Model
{
    public $dispatchesEvents = [
        'created' => CustomCreatedEvent::class,
        'updated' => CustomUpdatedEvent::class,
    ];
    public $table = 'test_model1';
    public $timestamps = false;
    protected $guarded = [];
}

class CustomCreatedEvent
{
    //
}

class CustomUpdatedEvent
{
    //
}
