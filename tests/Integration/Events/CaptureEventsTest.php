<?php

namespace Illuminate\Tests\Integration\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Events\EventCollection;
use Illuminate\Support\Facades\Event;
use Orchestra\Testbench\TestCase;

class CaptureEventsTest extends TestCase
{
    public function testCaptureEvents()
    {
        unset($_SERVER['__event.test']);

        Event::listen('foo', function ($foo) {
            $_SERVER['__event.test'] = $foo;
        });

        $captured = Event::capture(function () {
            Event::dispatch('foo', ['bar']);

            $this->assertArrayNotHasKey('__event.test', $_SERVER);
        });

        $this->assertArrayNotHasKey('__event.test', $_SERVER);

        foreach ($captured as $args) {
            Event::dispatch(...$args);
        }

        $this->assertSame('bar', $_SERVER['__event.test']);
    }

    public function testCaptureModelEvents()
    {
        $_SERVER['__model_event.test'] = [];

        CaptureTestModel::saved(function () {
            $_SERVER['__model_event.test'][] = 'saved';
        });

        $captured = Event::capture(function () {
            $model = new CaptureTestModel();
            $model->fireModelEvent('saved', false);

            $this->assertSame([], $_SERVER['__model_event.test']);
        });

        $this->assertSame([], $_SERVER['__model_event.test']);

        foreach ($captured as $args) {
            Event::dispatch(...$args);
        }

        $this->assertContains('saved', $_SERVER['__model_event.test']);
    }

    public function testCaptureSpecificModelEvents()
    {
        $_SERVER['__model_events'] = [];

        CaptureTestModel::creating(function () {
            $_SERVER['__model_events'][] = 'creating';
        });

        CaptureTestModel::saved(function () {
            $_SERVER['__model_events'][] = 'saved';
        });

        $captured = Event::capture(function () {
            $model = new CaptureTestModel();
            $model->fireModelEvent('creating');
            $model->fireModelEvent('saved');

            $this->assertSame(['creating'], $_SERVER['__model_events']);
        }, ['eloquent.saved: '.CaptureTestModel::class]);

        $this->assertSame(['creating'], $_SERVER['__model_events']);

        foreach ($captured as $args) {
            Event::dispatch(...$args);
        }

        $this->assertSame(['creating', 'saved'], $_SERVER['__model_events']);
    }

    public function testCaptureEmptyEvents()
    {
        $captured = Event::capture(function () {
            // No events dispatched
        });

        $this->assertInstanceOf(EventCollection::class, $captured);
        $this->assertEmpty($captured);
    }
}

class CaptureTestModel extends Model
{
    public function fireModelEvent($event, $halt = true)
    {
        return parent::fireModelEvent($event, $halt);
    }
}
