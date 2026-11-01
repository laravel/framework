<?php

namespace Illuminate\Tests\Integration\Events;

use Illuminate\Contracts\Events\RunConditionally;
use Illuminate\Database\DatabaseTransactionsManager;
use Illuminate\Support\Facades\Event;
use Orchestra\Testbench\TestCase;

class ListenerTest extends TestCase
{
    protected function tearDown(): void
    {
        ListenerTestListener::$ran = false;
        ListenerTestListenerAfterCommit::$ran = false;
        ListenerTestConditionalListenerAfterCommit::$ran = false;
        ListenerTestConditionalListenerAfterCommit::$shouldRun = true;

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

    public function testConditionallyRunnableClassListenerRunsAfterCommit()
    {
        $manager = new DatabaseTransactionsManager;
        $this->app->singleton('db.transactions', fn () => $manager);

        Event::listen(ListenerTestEvent::class, ListenerTestConditionalListenerAfterCommit::class);

        ListenerTestConditionalListenerAfterCommit::$shouldRun = true;

        $manager->begin('default', 1);

        Event::dispatch(new ListenerTestEvent);

        $this->assertFalse(ListenerTestConditionalListenerAfterCommit::$ran);

        $manager->commit('default', 1, 0);

        $this->assertTrue(ListenerTestConditionalListenerAfterCommit::$ran);
    }

    public function testConditionallyRunnableClassListenerIsNeverRunWhenShouldRunReturnsFalse()
    {
        $manager = new DatabaseTransactionsManager;
        $this->app->singleton('db.transactions', fn () => $manager);

        Event::listen(ListenerTestEvent::class, ListenerTestConditionalListenerAfterCommit::class);

        ListenerTestConditionalListenerAfterCommit::$shouldRun = false;

        $manager->begin('default', 1);

        Event::dispatch(new ListenerTestEvent);

        $manager->commit('default', 1, 0);

        $this->assertFalse(ListenerTestConditionalListenerAfterCommit::$ran);
    }

    public function testConditionallyRunnableClassListenerIsSkippedWhenShouldRunChangesBeforeCommit()
    {
        $manager = new DatabaseTransactionsManager;
        $this->app->singleton('db.transactions', fn () => $manager);

        Event::listen(ListenerTestEvent::class, ListenerTestConditionalListenerAfterCommit::class);

        ListenerTestConditionalListenerAfterCommit::$shouldRun = true;

        $manager->begin('default', 1);

        Event::dispatch(new ListenerTestEvent);

        ListenerTestConditionalListenerAfterCommit::$shouldRun = false;

        $this->assertFalse(ListenerTestConditionalListenerAfterCommit::$ran);

        $manager->commit('default', 1, 0);

        $this->assertFalse(ListenerTestConditionalListenerAfterCommit::$ran);
    }

    public function testConditionallyRunnableClassListenerRunsWhenShouldRunChangesBeforeCommit()
    {
        $manager = new DatabaseTransactionsManager;
        $this->app->singleton('db.transactions', fn () => $manager);

        Event::listen(ListenerTestEvent::class, ListenerTestConditionalListenerAfterCommit::class);

        ListenerTestConditionalListenerAfterCommit::$shouldRun = false;

        $manager->begin('default', 1);

        Event::dispatch(new ListenerTestEvent);

        ListenerTestConditionalListenerAfterCommit::$shouldRun = true;

        $this->assertFalse(ListenerTestConditionalListenerAfterCommit::$ran);

        $manager->commit('default', 1, 0);

        $this->assertTrue(ListenerTestConditionalListenerAfterCommit::$ran);
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

class ListenerTestConditionalListenerAfterCommit implements RunConditionally
{
    public static $ran = false;

    public static $shouldRun = true;

    public $afterCommit = true;

    public function shouldRun($event): bool
    {
        return static::$shouldRun;
    }

    public function handle()
    {
        static::$ran = true;
    }
}
