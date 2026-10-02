<?php

namespace Illuminate\Tests\Integration\Events;

use Illuminate\Database\DatabaseTransactionsManager;
use Illuminate\Support\Facades\Event;
use Orchestra\Testbench\TestCase;

class ListenerTest extends TestCase
{
    protected function tearDown(): void
    {
        ListenerTestListener::$ran = false;
        ListenerTestListenerAfterCommit::$ran = false;

        parent::tearDown();
    }

    public function testClassListenerRunsImmediatelyInsideTransaction()
    {
        $manager = new DatabaseTransactionsManager;
        $this->app->singleton('db.transactions', fn () => $manager);

        Event::listen(ListenerTestEvent::class, ListenerTestListener::class);

        $manager->begin('default', 1);

        Event::dispatch(new ListenerTestEvent);

        $this->assertTrue(ListenerTestListener::$ran);
    }

    public function testClassListenerDoesntRunInsideTransaction()
    {
        $manager = new DatabaseTransactionsManager;
        $this->app->singleton('db.transactions', fn () => $manager);

        Event::listen(ListenerTestEvent::class, ListenerTestListenerAfterCommit::class);

        $manager->begin('default', 1);

        Event::dispatch(new ListenerTestEvent);

        $this->assertFalse(ListenerTestListenerAfterCommit::$ran);

        $manager->commit('default', 1, 0);

        $this->assertTrue(ListenerTestListenerAfterCommit::$ran);
    }
}

class ListenerTestEvent
{
    //
}

class ListenerTestListener
{
    public static $ran = false;

    public function handle()
    {
        static::$ran = true;
    }
}

class ListenerTestListenerAfterCommit
{
    public static $ran = false;

    public $afterCommit = true;

    public function handle()
    {
        static::$ran = true;
    }
}
