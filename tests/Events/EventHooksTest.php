<?php

namespace Illuminate\Tests\Events;

use Illuminate\Container\Container;
use Illuminate\Events\Dispatcher;
use Illuminate\Events\EventPropagationException;
use Illuminate\Support\Arr;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;

class EventHooksTest extends TestCase
{
    private TestDispatcher $dispatcher;

    private array $invoked = [];

    private array $order = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->dispatcher = new TestDispatcher(new Container);
    }

    public function test_registers_wildcard_callbacks_correctly(): void
    {
        $this->dispatcher->before($callback = static fn (): string => static::VALID_CALLBACK);

        $this->assertCount(1, $this->dispatcher->callbacks()[static::HOOK_BEFORE][static::WILDCARD],
            'One before callback should be registered for wildcard events.');
        $this->assertEquals(Arr::first($this->dispatcher->callbacks()[static::HOOK_BEFORE][static::WILDCARD]), $callback,
            'The expected before callback should be registered for wildcard events.');
    }

    public function test_registers_before_callbacks_correctly(): void
    {
        $this->dispatcher->before(static::EVENT, $callback = static fn (): string => static::VALID_CALLBACK);

        $this->assertCount(1, $this->dispatcher->callbacks()[static::HOOK_BEFORE][static::EVENT],
            'One before callback should be registered for the event.');
        $this->assertEquals(Arr::first($this->dispatcher->callbacks()[static::HOOK_BEFORE][static::EVENT]), $callback,
            'The expected before callback should be registered for the event.');
    }

    public function test_registers_after_callbacks_correctly(): void
    {
        $this->dispatcher->after(static::EVENT, $callback = static fn (): string => static::VALID_CALLBACK);

        $this->assertCount(1, $this->dispatcher->callbacks()[static::HOOK_AFTER][static::EVENT],
            'One after callback should be registered for the event.');
        $this->assertEquals(Arr::first($this->dispatcher->callbacks()[static::HOOK_AFTER][static::EVENT]), $callback,
            'The expected after callback should be registered for the event.');
    }

    public function test_registers_failure_callbacks_correctly(): void
    {
        $this->dispatcher->failure(static::EVENT, $callback = static fn (): string => static::VALID_CALLBACK);

        $this->assertCount(1, $this->dispatcher->callbacks()[static::HOOK_FAILURE][static::EVENT],
            'One failure callback should be registered for the event.');
        $this->assertEquals(Arr::first($this->dispatcher->callbacks()[static::HOOK_FAILURE][static::EVENT]), $callback,
            'The expected failure callback should be registered for the event.');
    }

    public function test_registers_multiple_callbacks_for_single_event_correctly(): void
    {
        $this->dispatcher->before(static::EVENT, $callback1 = static fn (): string => static::VALID_CALLBACK.'1');
        $this->dispatcher->before(static::EVENT, $callback2 = static fn (): string => static::VALID_CALLBACK.'2');

        $this->assertCount(2, $this->dispatcher->callbacks()[static::HOOK_BEFORE][static::EVENT],
            'Two before callbacks should be registered for the event.');
        $this->assertEquals(Arr::first($this->dispatcher->callbacks()[static::HOOK_BEFORE][static::EVENT]), $callback1,
            'The expected first before callback should be registered for the event.');
        $this->assertEquals(Arr::last($this->dispatcher->callbacks()[static::HOOK_BEFORE][static::EVENT]), $callback2,
            'The expected second before callback should be registered for the event.');
    }

    public function test_registers_callbacks_for_multiple_events_correctly(): void
    {
        $this->dispatcher->before(static::EVENT.'1', $callback1 = static fn (): string => static::VALID_CALLBACK);
        $this->dispatcher->before(static::EVENT.'2', $callback2 = static fn (): string => static::VALID_CALLBACK);

        $callbacks1 = $this->dispatcher->callbacks(static::HOOK_BEFORE, static::EVENT.'1');
        $callbacks2 = $this->dispatcher->callbacks(static::HOOK_BEFORE, static::EVENT.'2');

        $this->assertCount(1, $callbacks1[static::EVENT.'1'],
            'One before callback should be registered for event 1.');
        $this->assertCount(1, $callbacks2[static::EVENT.'2'],
            'One before callback should be registered for event 2.');
        $this->assertEquals(Arr::first($callbacks1[static::EVENT.'1']), $callback1,
            'The expected before callback should be registered for event 1.');
        $this->assertEquals(Arr::first($callbacks2[static::EVENT.'2']), $callback2,
            'The expected before callback should be registered for event 2.');
    }

    public function test_registers_array_of_callbacks_for_single_event_correctly(): void
    {
        $this->dispatcher->before(static::EVENT, [
            $callback1 = static fn (): string => static::VALID_CALLBACK.'1',
            $callback2 = static fn (): string => static::VALID_CALLBACK.'2',
        ]);

        $callbacks = $this->dispatcher->callbacks(static::HOOK_BEFORE, static::EVENT);

        $this->assertCount(2, $callbacks[static::EVENT],
            'Two before callbacks should be registered for the event.');
        $this->assertEquals(Arr::first($this->dispatcher->callbacks()[static::HOOK_BEFORE][static::EVENT]), $callback1,
            'The expected first before callback should be registered for the event.');
        $this->assertEquals(Arr::last($this->dispatcher->callbacks()[static::HOOK_BEFORE][static::EVENT]), $callback2,
            'The expected second before callback should be registered for the event.');
    }

    public function test_registers_single_callback_for_array_of_events_correctly(): void
    {
        $this->dispatcher->before([static::EVENT.'1', static::EVENT.'2'], $callback = static fn (): string => static::VALID_CALLBACK);

        $this->assertCount(1, $this->dispatcher->callbacks()[static::HOOK_BEFORE][static::EVENT.'1'],
            'One before callback should be registered for event 1.');
        $this->assertCount(1, $this->dispatcher->callbacks()[static::HOOK_BEFORE][static::EVENT.'2'],
            'One before callback should be registered for event 2.');
        $this->assertEquals(Arr::first($this->dispatcher->callbacks()[static::HOOK_BEFORE][static::EVENT.'1']), $callback,
            'The expected before callback should be registered for event 1.');
        $this->assertEquals(Arr::first($this->dispatcher->callbacks()[static::HOOK_BEFORE][static::EVENT.'2']), $callback,
            'The expected before callback should be registered for event 2.');
    }

    public function test_registers_global_callbacks_correctly(): void
    {
        $this->dispatcher->before($callback = static fn (): string => static::VALID_CALLBACK);

        $this->assertArrayHasKey(static::WILDCARD, $this->dispatcher->callbacks(static::HOOK_BEFORE),
            'The callback array should have an entry for the before hook for wildcard events.');
        $this->assertCount(1, $this->dispatcher->callbacks()[static::HOOK_BEFORE][static::WILDCARD],
            'One before callback should be registered for wildcard events.');
        $this->assertEquals(Arr::first($this->dispatcher->callbacks()[static::HOOK_BEFORE][static::WILDCARD]), $callback,
            'The expected before callback should be registered for wildcard events.');
    }

    public function test_registered_callbacks_can_be_accessed_correctly(): void
    {
        $this->dispatcher->before(static::EVENT.'1', $callback1 = static fn (): string => static::VALID_CALLBACK.'1');
        $this->dispatcher->after(static::EVENT.'2', $callback2 = static fn (): string => static::VALID_CALLBACK.'2');
        $this->dispatcher->failure($callback3 = static fn (): string => static::VALID_CALLBACK.'3');

        $this->assertArrayHasKey(static::HOOK_BEFORE, $this->dispatcher->callbacks(),
            'The callback array should have an entry for the before hook when calling the callbacks() method without arguments.');
        $this->assertArrayHasKey(static::EVENT.'1', $this->dispatcher->callbacks(static::HOOK_BEFORE),
            'The callback array should have an entry for the before hook for event 1 when calling the callbacks() method with the before hook argument.');
        $this->assertEquals(Arr::first($this->dispatcher->callbacks()[static::HOOK_BEFORE][static::EVENT.'1']), $callback1,
            'The expected before callback should be registered for event 1 when calling the callbacks() method without arguments.');
        $this->assertEquals(Arr::first($this->dispatcher->callbacks(static::HOOK_BEFORE)[static::EVENT.'1']), $callback1,
            'The expected before callback should be registered for event 1 when calling the callbacks() method with the before hook argument.');
        $this->assertArrayHasKey(static::HOOK_BEFORE, $this->dispatcher->callbacks(static::EVENT.'1'),
            'The callback array should have an entry for the before hook when calling the callbacks() method with the event argument.');
        $this->assertArrayHasKey(static::EVENT.'1', $this->dispatcher->callbacks(static::EVENT.'1')[static::HOOK_BEFORE],
            'The callback array should have an entry for the before hook for event 1 when calling the callbacks() method with the event argument.');
        $this->assertEquals(Arr::first($this->dispatcher->callbacks(static::EVENT.'1')[static::HOOK_BEFORE][static::EVENT.'1']), $callback1,
            'The expected before callback should be registered for event 1 when calling the callbacks() method with the event argument.');

        $this->assertArrayHasKey(static::HOOK_AFTER, $this->dispatcher->callbacks(),
            'The callback array should have an entry for the after hook when calling the callbacks() method without arguments.');
        $this->assertArrayHasKey(static::EVENT.'2', $this->dispatcher->callbacks(static::HOOK_AFTER),
            'The callback array should have an entry for the after hook for event 2 when calling the callbacks() method with the after hook argument.');
        $this->assertEquals(Arr::first($this->dispatcher->callbacks()[static::HOOK_AFTER][static::EVENT.'2']), $callback2,
            'The expected after callback should be registered for event 2 when calling the callbacks() method without arguments.');
        $this->assertEquals(Arr::first($this->dispatcher->callbacks(static::HOOK_AFTER)[static::EVENT.'2']), $callback2,
            'The expected after callback should be registered for event 2 when calling the callbacks() method with the after hook argument.');
        $this->assertArrayHasKey(static::HOOK_AFTER, $this->dispatcher->callbacks(static::EVENT.'2'),
            'The callback array should have an entry for the after hook when calling the callbacks() method with the event argument.');
        $this->assertArrayHasKey(static::EVENT.'2', $this->dispatcher->callbacks(static::EVENT.'2')[static::HOOK_AFTER],
            'The callback array should have an entry for the after hook for event 2 when calling the callbacks() method with the event argument.');
        $this->assertEquals(Arr::first($this->dispatcher->callbacks(static::EVENT.'2')[static::HOOK_AFTER][static::EVENT.'2']), $callback2,
            'The expected after callback should be registered for event 2 when calling the callbacks() method with the event argument.');

        $this->assertArrayHasKey(static::HOOK_FAILURE, $this->dispatcher->callbacks(),
            'The callback array should have an entry for the failure hook when calling the callbacks() method without arguments.');
        $this->assertArrayHasKey(static::WILDCARD, $this->dispatcher->callbacks(static::HOOK_FAILURE),
            'The callback array should have an entry for the failure hook for wildcard events when calling the callbacks() method with the failure hook argument.');
        $this->assertEquals(Arr::first($this->dispatcher->callbacks()[static::HOOK_FAILURE][static::WILDCARD]), $callback3,
            'The expected failure callback should be registered for wildcard events when calling the callbacks() method without arguments.');
        $this->assertEquals(Arr::first($this->dispatcher->callbacks(static::HOOK_FAILURE)[static::WILDCARD]), $callback3,
            'The expected failure callback should be registered for wildcard events when calling the callbacks() method with the failure hook argument.');
        $this->assertArrayHasKey(static::HOOK_FAILURE, $this->dispatcher->callbacks(static::WILDCARD),
            'The callback array should have an entry for the failure hook when calling the callbacks() method with the event argument.');
        $this->assertArrayHasKey(static::WILDCARD, $this->dispatcher->callbacks(static::WILDCARD)[static::HOOK_FAILURE],
            'The callback array should have an entry for the failure hook for wildcard events when calling the callbacks() method with the event argument.');
        $this->assertEquals(Arr::first($this->dispatcher->callbacks(static::WILDCARD)[static::HOOK_FAILURE][static::WILDCARD]), $callback3,
            'The expected failure callback should be registered for wildcard events when calling the callbacks() method with the event argument.');
    }

    public function test_registered_hierarchical_callbacks_can_be_accessed_correctly(): void
    {
        $this->dispatcher->before(EventInterface::class, $interface = static fn (): string => static::VALID_CALLBACK.'interface');
        $this->dispatcher->before(ParentEvent::class, $parent = static fn (): string => static::VALID_CALLBACK.'parent');
        $this->dispatcher->before(ChildEvent::class, $child = static fn (): string => static::VALID_CALLBACK.'child');
        $this->dispatcher->before(static::WILDCARD, $wildcard = static fn (): string => static::EVENT.static::WILDCARD);

        $this->assertEquals($wildcard, Arr::first($this->dispatcher->callbacks(EventInterface::class)[static::HOOK_BEFORE][static::WILDCARD]),
            'The expected interface before callback should be registered for the event when calling the callbacks() method with the event argument.');
        $this->assertEquals($interface, Arr::first($this->dispatcher->callbacks(static::HOOK_BEFORE)[EventInterface::class]),
            'The expected parent before callback should be registered for the event when calling the callbacks() method with the event argument.');
        $this->assertEquals($parent, Arr::first($this->dispatcher->callbacks(static::HOOK_BEFORE)[ParentEvent::class]),
            'The expected event before callback should be registered for the event when calling the callbacks() method with the event argument.');
        $this->assertEquals($child, Arr::first($this->dispatcher->callbacks(static::HOOK_BEFORE)[ChildEvent::class]),
            'The expected wildcard before callback should be registered for the event when calling the callbacks() method with the event argument.');
    }

    public function test_invalid_callback_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->dispatcher->before(static::EVENT, static::INVALID_CALLBACK);
    }

    public function test_callback_with_nonexistent_class_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->call('validateCallback', [static::INVALID_CLASS]);
    }

    public function test_callback_with_nonexistent_method_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->call('validateCallback', [MethodCallbackClass::class.'@'.static::INVALID_METHOD]);
    }

    public function test_invoking_invalid_callback_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->call('invokeCallback', [static::INVALID_CALLBACK, []]);
    }

    public function test_validates_callback_string_with_at_notation(): void
    {
        $this->expectNotToPerformAssertions();

        $this->call('validateCallback', [MethodCallbackClass::class.'@'.static::VALID_METHOD]);
    }

    public function test_validates_callback_string_with_double_colon_notation(): void
    {
        $this->expectNotToPerformAssertions();

        $this->call('validateCallback', [MethodCallbackClass::class.'::'.static::VALID_METHOD]);
    }

    public function test_invokes_object_and_method_format_callback_correctly(): void
    {
        $this->call('invokeCallback', [[$object = new MethodCallbackClass, static::VALID_METHOD], static::PAYLOAD]);

        $this->assertEquals(Arr::first(static::PAYLOAD), $object->payload,
            'The callback registered with object and method should have been invoked correctly, receiving the expected payload.');
    }

    public function test_invokes_class_method_with_at_notation_correctly(): void
    {
        $container = tap($this->call('container'), static function (Container $container): void {
            $container->singleton(MethodCallbackClass::class, static fn (): MethodCallbackClass => new MethodCallbackClass);
        });

        $this->call('invokeCallback', [MethodCallbackClass::class.'@'.static::VALID_METHOD, static::PAYLOAD]);

        $this->assertEquals(Arr::first(static::PAYLOAD), $container->make(MethodCallbackClass::class)->payload,
            'The callback registered with classname@method notation should have been invoked correctly, receiving the expected payload.');
    }

    public function test_invokes_class_method_with_double_colon_notation_correctly(): void
    {
        $container = tap($this->call('container'), static function (Container $container): void {
            $container->singleton(MethodCallbackClass::class, static fn (): MethodCallbackClass => new MethodCallbackClass);
        });

        $this->call('invokeCallback', [MethodCallbackClass::class.'::'.static::VALID_METHOD, static::PAYLOAD]);

        $this->assertEquals(Arr::first(static::PAYLOAD), $container->make(MethodCallbackClass::class)->payload,
            'The callback registered with classname::method notation should have been invoked correctly, receiving the expected payload.');
    }

    public function test_invokes_class_handle_method_with_classname_notation_correctly(): void
    {
        $container = tap($this->call('container'), static function (Container $container): void {
            $container->singleton(MethodCallbackClass::class, static fn (): MethodCallbackClass => new MethodCallbackClass);
        });

        $this->call('invokeCallback', [MethodCallbackClass::class, static::PAYLOAD]);

        $this->assertEquals(Arr::first(static::PAYLOAD), $container->make(MethodCallbackClass::class)->payload,
            'The callback registered with classname notation having a handle() method should have been invoked correctly, receiving the expected payload.');
    }

    public function test_invokes_invokable_class_correctly(): void
    {
        $container = tap($this->call('container'), static function (Container $container): void {
            $container->singleton(InvokableCallbackClass::class, static fn (): InvokableCallbackClass => new InvokableCallbackClass);
        });

        $this->call('invokeCallback', [InvokableCallbackClass::class, static::PAYLOAD]);

        $this->assertEquals(Arr::first(static::PAYLOAD), $container->make(InvokableCallbackClass::class)->payload,
            'The callback registered with classname notation having an __invoke() method should have been invoked, receiving the expected payload.');
    }

    public function test_invokes_closure_callback_correctly(): void
    {
        $called = false;
        $callback = static function (mixed $payload) use (&$called): void {
            $called = $payload;
        };

        $this->call('invokeCallback', [$callback, static::PAYLOAD]);

        $this->assertEquals(Arr::first(static::PAYLOAD), $called,
            'The callback registered with a closure should have been invoked, receiving the expected payload.');
    }

    public function test_hierarchical_callbacks_are_called_correctly(): void
    {
        $parent = function (): void {
            $this->invoked[] = static::VALID_CALLBACK.'parent';
        };

        $interface = function (): void {
            $this->invoked[] = static::VALID_CALLBACK.'interface';
        };

        $this->dispatcher->before(EventInterface::class, $interface);
        $this->dispatcher->before(ParentEvent::class, $parent);

        $this->call('invokeCallbacks', [static::HOOK_BEFORE, ChildEvent::class, [new ChildEvent]]);

        $this->assertCount(2, $this->invoked,
            'Two callbacks should have been invoked.');
        $this->assertEquals(static::VALID_CALLBACK.'parent', Arr::first($this->invoked),
            'The hierarchical parent callback should have been invoked first.');
        $this->assertEquals(static::VALID_CALLBACK.'interface', Arr::last($this->invoked),
            'The hierarchical interface callback should have been invoked second.');
    }

    public function test_before_callbacks_are_called_before_listeners(): void
    {
        $callback = function (): void {
            $this->order[] = static::VALID_CALLBACK;
        };

        $this->dispatcher->before(static::EVENT, $callback);

        $this->dispatcher->listen(
            static::EVENT,
            function (): void {
                $this->order[] = static::LISTENER;
                $this->invoked[] = static::VALID_CALLBACK;
            }
        );

        $this->call('invokeListeners', [static::EVENT, static::PAYLOAD]);

        $this->assertCount(1, $this->dispatcher->invoked,
            'One before callback should have been invoked.');
        $this->assertCount(1, $this->invoked,
            'One listener should have been invoked.');
        $this->assertEquals(static::VALID_CALLBACK, Arr::first($this->order),
            'The expected before callback should have been invoked first.');
        $this->assertEquals(static::LISTENER, Arr::last($this->order),
            'The expected listener should have been invoked last.');
    }

    public function test_after_callbacks_are_called_after_listeners(): void
    {
        $callback = function (): void {
            $this->order[] = static::VALID_CALLBACK;
        };

        $this->dispatcher->after(static::EVENT, $callback);

        $this->dispatcher->listen(
            static::EVENT,
            function (): void {
                $this->invoked[] = static::VALID_CALLBACK;
                $this->order[] = static::LISTENER;
            }
        );

        $this->call('invokeListeners', [static::EVENT, static::PAYLOAD]);

        $this->assertCount(1, $this->invoked,
            'One listener should have been invoked.');
        $this->assertCount(1, $this->dispatcher->invoked,
            'One after callback should have been invoked.');
        $this->assertEquals(static::LISTENER, Arr::first($this->order),
            'The expected listener should have been invoked first.');
        $this->assertEquals(static::VALID_CALLBACK, Arr::last($this->order),
            'The expected after callback should have been invoked last.');
    }

    public function test_failure_callbacks_are_called_on_listener_failure(): void
    {
        $callback = function (): void {
            $this->order[] = static::VALID_CALLBACK;
            $this->invoked[] = static::VALID_CALLBACK;
        };

        $this->dispatcher->failure(static::EVENT, $callback);

        $listeners = [
            function (): bool {
                $this->order[] = static::LISTENER.'1';

                return false;
            },
            function (): void {
                $this->order[] = static::LISTENER.'2';
            },
        ];

        foreach ($listeners as $listener) {
            $this->dispatcher->listen(static::EVENT, $listener);
        }

        $this->call('invokeListeners', [static::EVENT, static::PAYLOAD]);

        foreach ($this->dispatcher->invoked as $callback) {
            $this->assertNotEquals(static::HOOK_AFTER, $callback['hook']);
        }

        $this->assertCount(1, $this->dispatcher->invoked,
            'One failure callback should have been invoked.');
        $this->assertCount(1, $this->invoked,
            'One listener should have been invoked.');
        $this->assertEquals(static::LISTENER.'1', Arr::first($this->order),
            'The expected listener should have been invoked first.');
        $this->assertEquals(static::VALID_CALLBACK, Arr::last($this->order),
            'The expected failure callback should have been invoked last.');
    }

    public function test_event_propagation_exception_halts_callback_processing(): void
    {
        $callbacks = [
            function (): void {
                $this->invoked[] = static::VALID_CALLBACK.'1';
            },
            function (): void {
                $this->invoked[] = static::VALID_CALLBACK.'2';
                throw new EventPropagationException;
            },
            function (): void {
                $this->invoked[] = static::VALID_CALLBACK.'3';
            },
        ];

        $this->dispatcher->before(static::EVENT, $callbacks);

        try {
            $this->call('invokeCallbacks', [static::HOOK_BEFORE, static::EVENT, []]);
            $this->fail('Expected EventPropagationException was not thrown');
        } catch (EventPropagationException) {
            $this->assertEquals([static::VALID_CALLBACK.'1', static::VALID_CALLBACK.'2'], $this->invoked,
                'Only the expected before callbacks for the event should have been invoked.');
        }
    }

    public function test_event_propagation_exception_prevents_listener_processing(): void
    {
        $callback = false;

        $this->dispatcher->before(static::EVENT, [
            static function () use (&$callback): void {
                $callback = true;
                throw new EventPropagationException;
            },
        ]);

        $listener = false;

        $this->dispatcher->listen(
            static::EVENT,
            static function () use (&$listener): void {
                $listener = true;
            }
        );

        $result = $this->call('invokeListeners', [static::EVENT, static::PAYLOAD]);

        $this->assertNull($result, 'The result of listener dispatch should be null when propagation is halted.');
        $this->assertTrue($callback, 'The before callback for the event should have been invoked.');
        $this->assertFalse($listener, 'The listener for the event should not be invoked when propagation is halted.');
    }

    public function test_multiple_listeners_with_before_and_after_callbacks_execute_in_order(): void
    {
        $before = [
            function (): void {
                $this->order[] = static::VALID_CALLBACK.'-'.static::HOOK_BEFORE;
            },
        ];

        $after = [
            function (): void {
                $this->order[] = static::VALID_CALLBACK.'-'.static::HOOK_AFTER;
            },
        ];

        $this->dispatcher->before(static::EVENT, $before);
        $this->dispatcher->after(static::EVENT, $after);

        $listeners = [
            function (): void {
                $this->invoked[] = static::LISTENER.'1';
                $this->order[] = static::LISTENER.'1';
            },
            function (): void {
                $this->invoked[] = static::LISTENER.'2';
                $this->order[] = static::LISTENER.'2';
            },
        ];

        foreach ($listeners as $listener) {
            $this->dispatcher->listen(static::EVENT, $listener);
        }

        $this->call('invokeListeners', [static::EVENT, static::PAYLOAD]);

        $this->assertCount(2, $this->dispatcher->invoked,
            'Two callbacks should have been invoked.');
        $this->assertCount(2, $this->invoked,
            'Two listeners should have been invoked.');
        $this->assertEquals(static::VALID_CALLBACK.'-'.static::HOOK_BEFORE, $this->order[0],
            'The before callback should have been invoked first.');
        $this->assertEquals(static::LISTENER.'1', $this->order[1],
            'The first listener should have been invoked second.');
        $this->assertEquals(static::LISTENER.'2', $this->order[2],
            'The second listener should have been invoked third');
        $this->assertEquals(static::VALID_CALLBACK.'-'.static::HOOK_AFTER, $this->order[3],
            'The before callback should have been invoked fourth.');
    }

    public function test_event_hook_methods_are_called_during_dispatch_correctly(): void
    {
        $this->dispatcher->dispatch($event = new EventWithHooks);

        $this->assertTrue($event->beforeCalled, 'Event::before() should be called.');
        $this->assertTrue($event->afterCalled, 'Event::after() should be called.');
        $this->assertFalse($event->failureCalled, 'Event::failure() should not be called.');
    }

    public function test_event_with_failure_method_is_called_when_listener_returns_false(): void
    {
        $this->dispatcher->listen(EventWithHooks::class, static fn (): bool => false);

        $this->dispatcher->dispatch($event = new EventWithHooks);

        $this->assertTrue($event->beforeCalled, 'Event::before() should be called.');
        $this->assertFalse($event->afterCalled, 'Event::after() should not be called.');
        $this->assertTrue($event->failureCalled, 'Event::failure() should be called.');
    }

    public function test_event_methods_are_called_in_correct_order(): void
    {
        $event = tap(
            new EventWithHooks,
            function (EventWithHooks $event): void {
                $event->callback = function ($method): void {
                    $this->invoked[] = $method;
                };
            }
        );

        $this->dispatcher->listen(
            get_class($event),
            function (): void {
                $this->invoked[] = static::LISTENER;
            }
        );

        $this->dispatcher->dispatch($event);

        $this->assertEquals([static::HOOK_BEFORE, static::LISTENER, static::HOOK_AFTER], $this->invoked,
            'The before, listener, and after callbacks should be called in that order.');
    }

    public function test_event_methods_are_called_with_correct_payload(): void
    {
        $this->dispatcher->dispatch($event = new EventWithHooks);

        $this->assertSame($event, Arr::first($event->beforePayload),
            'The before callback should receive the event payload.');
        $this->assertSame($event, Arr::first($event->afterPayload),
            'The after callback should receive the event payload.');
    }

    public function test_callback_aggregation_includes_both_wildcard_and_specific_callbacks(): void
    {
        $wildcardCallback = static fn (): string => static::WILDCARD;
        $specificCallback = static fn (): string => static::EVENT;

        $this->dispatcher->before($wildcardCallback);
        $this->dispatcher->before(static::EVENT, $specificCallback);

        $callbacks = $this->call('aggregateCallbacks', [static::HOOK_BEFORE, static::EVENT, []]);

        $this->assertCount(2, $callbacks,
            'Two callbacks for the event should be aggregated.');
        $this->assertSame(static::WILDCARD, Arr::first($callbacks)(),
            'The wildcard callback should be first in the aggregation.');
        $this->assertSame(static::EVENT, Arr::last($callbacks)(),
            'The event-specific callback should be last in the aggregation.');
    }

    public function test_callback_aggregation_with_invalid_hook_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->call('aggregateCallbacks', [static::INVALID_HOOK, static::EVENT, []]);
    }

    public function test_callback_aggregation_includes_all_registered_callbacks(): void
    {
        $wildcard = [
            $wildcard1 = function (): void {
                $this->invoked[] = static::VALID_CALLBACK;
            },
            $wildcard2 = function (): void {
                $this->invoked[] = static::VALID_CALLBACK;
            },
        ];

        $event = [
            $event1 = function (): void {
                $this->invoked[] = static::VALID_CALLBACK;
            },
            $event2 = function (): void {
                $this->invoked[] = static::VALID_CALLBACK;
            },
        ];

        $this->dispatcher->before($wildcard);
        $this->dispatcher->before(static::EVENT, $event);

        $aggregated = $this->call('aggregateCallbacks', [static::HOOK_BEFORE, static::EVENT, []]);

        $this->assertCount(4, $aggregated,
            'Four callbacks for the event should be aggregated.');
        $this->assertSame($wildcard1, $aggregated[0],
            'The wildcard1 callback should be first in the aggregation.');
        $this->assertSame($wildcard2, $aggregated[1],
            'The wildcard2 callback should be second in the aggregation.');
        $this->assertSame($event1, $aggregated[2],
            'The event1 callback should be third in the aggregation.');
        $this->assertSame($event2, $aggregated[3],
            'The event2 callback should be fourth in the aggregation.');
    }

    public function test_callback_aggregation_includes_event_object_hook_methods(): void
    {
        $aggregated = $this->call('aggregateCallbacks', [static::HOOK_BEFORE, EventWithHooks::class, [$event = new EventWithHooks]]);

        $this->assertCount(1, $aggregated,
            'One callback for the event should be aggregated.');
        $this->assertSame([$event, static::HOOK_BEFORE], Arr::first($aggregated),
            'The expected callback for the event should exist in the aggregation.');
    }

    public function test_callbacks_are_ordered_for_before_hook_correctly(): void
    {
        $registered = [
            static::WILDCARD => [
                function (): void {
                    $this->invoked[] = static::WILDCARD;
                },
            ],
            static::EVENT => [
                function (): void {
                    $this->invoked[] = static::EVENT;
                },
            ],
            EventWithHooks::class => [
                function (): void {
                    $this->invoked[] = EventWithHooks::class;
                },
            ],
        ];

        $ordered = $this->call('orderCallbacks', [static::HOOK_BEFORE, static::EVENT, $registered]);

        $this->assertSame($registered, $ordered,
            'Before callbacks should be ordered as follows: wildcard, event-specific, and event object.');
    }

    public function test_callbacks_are_ordered_for_after_hook_correctly(): void
    {
        $registered = [
            static::WILDCARD => [static fn (): string => static::VALID_CALLBACK],
            static::EVENT => [static fn (): string => static::VALID_CALLBACK],
            EventWithHooks::class => [static fn (): string => static::VALID_CALLBACK],
        ];

        $ordered = $this->call('orderCallbacks', [static::HOOK_AFTER, static::EVENT, $registered]);

        $this->assertSame(array_reverse($registered), $ordered,
            'After callbacks should be ordered as follows: event object, event-specific, and wildcard.');
    }

    public function test_callbacks_are_ordered_for_failure_hook_correctly(): void
    {
        $registered = [
            static::WILDCARD => [static fn (): string => static::VALID_CALLBACK],
            static::EVENT => [static fn (): string => static::VALID_CALLBACK],
            EventWithHooks::class => [static fn (): string => static::VALID_CALLBACK],
        ];

        $ordered = $this->call('orderCallbacks', [static::HOOK_FAILURE, static::EVENT, $registered]);

        $this->assertSame(array_reverse($registered), $ordered,
            'Failure callbacks should be ordered as follows: event object, event-specific, and wildcard.');
    }

    public function test_callbacks_throw_exception_for_invalid_hook_type(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->call('orderCallbacks', [static::INVALID_CALLBACK, static::EVENT, []]);
    }

    public function test_callbacks_are_combined_and_ordered_correctly(): void
    {
        $wildcard = [
            function (): void {
                $this->invoked[] = static::WILDCARD.'1';
            },
            function (): void {
                $this->invoked[] = static::WILDCARD.'2';
            },
        ];

        $event = [
            function (): void {
                $this->invoked[] = static::EVENT.'1';
            },
            function (): void {
                $this->invoked[] = static::EVENT.'2';
            },
        ];

        $this->dispatcher->after($wildcard);
        $this->dispatcher->after(static::EVENT.'1', $event);

        $prepared = $this->call('prepareCallbacks', [static::HOOK_AFTER, static::EVENT.'1', []]);

        foreach ($prepared as $callback) {
            $callback();
        }

        $this->assertCount(4, $prepared,
            'Four callbacks for the event should be prepared.');

        $this->assertEquals([static::EVENT.'2', static::EVENT.'1', static::WILDCARD.'2', static::WILDCARD.'1'], $this->invoked,
            'The expected after callbacks should be prepared, and ordered correctly: event-specific callbacks preceding wildcard callbacks.');
    }

    public function test_all_callbacks_are_invoked(): void
    {
        $callbacks = [
            function (): void {
                $this->invoked[] = static::VALID_CALLBACK.'1';
            },
            function (): void {
                $this->invoked[] = static::VALID_CALLBACK.'2';
            },
        ];

        $this->dispatcher->before(static::EVENT, $callbacks);

        $this->call('invokeCallbacks', [static::HOOK_BEFORE, static::EVENT, []]);

        $this->assertCount(2, $this->invoked,
            'Two callbacks for the event should have been invoked.');
        $this->assertEquals([static::VALID_CALLBACK.'1', static::VALID_CALLBACK.'2'], $this->invoked,
            'The expected before callbacks should have been invoked.');
    }

    public function test_callbacks_receive_and_process_payload_correctly(): void
    {
        $callbacks = [
            function (string $payload): void {
                $this->invoked[] = static::VALID_CALLBACK."1-{$payload}";
            },
            function (string $payload): void {
                $this->invoked[] = static::VALID_CALLBACK."2-{$payload}";
            },
        ];

        $this->dispatcher->before(static::EVENT, $callbacks);

        $this->call('invokeCallbacks', [static::HOOK_BEFORE, static::EVENT, static::PAYLOAD]);

        $this->assertEquals(static::VALID_CALLBACK.'1-'.Arr::first(static::PAYLOAD), Arr::first($this->invoked),
            'The first before callback for the event should have been invoked with its expected payload.');

        $this->assertEquals(static::VALID_CALLBACK.'2-'.Arr::first(static::PAYLOAD), Arr::last($this->invoked),
            'The second before callback for the event should have been invoked with its expected payload.');

    }

    public function test_callbacks_are_only_invoked_when_they_exist(): void
    {
        $this->set('callbacks', [static::HOOK_BEFORE => [static::EVENT => [static fn (): null => null]]]);

        $this->call('invokeListeners', [static::EVENT, static::PAYLOAD]);

        $this->assertCount(1, $this->dispatcher->invoked,
            'Only one callback for the event should have been invoked.');
        $this->assertEquals(static::HOOK_BEFORE, Arr::first($this->dispatcher->invoked)['hook'],
            'The before hook should have been invoked.');
        $this->assertEquals(static::EVENT, Arr::first($this->dispatcher->invoked)['event'],
            'The before callback should have been triggered by the specified event.');
        $this->assertEquals(static::PAYLOAD, Arr::first($this->dispatcher->invoked)['payload'],
            'The before callback should have received the expected event payload.');
    }

    public function test_no_callbacks_are_invoked_when_no_callbacks_exist_for_event(): void
    {
        $this->call('invokeListeners', [static::EVENT, static::PAYLOAD]);

        $this->assertEmpty($this->dispatcher->invoked,
            'No callbacks should have been invoked for the event.');
    }

    public function test_prepares_event_object_callback_when_present(): void
    {
        $callback = $this->call('prepareEventObjectCallback', [static::HOOK_BEFORE, [$event = new EventWithHooks]]);

        $this->assertSame([$event, static::HOOK_BEFORE], $callback,
            'The expected event object before callback should have been prepared.');
    }

    public function test_does_not_prepare_event_object_callbacks_when_hook_methods_are_missing(): void
    {
        $callback = $this->call('prepareEventObjectCallback', [static::HOOK_BEFORE, [new class {}]]);

        $this->assertEmpty($callback, 'No callbacks should be prepared for the event object as no hook methods are present.');
    }

    public function test_callback_detection_recognizes_wildcard_callbacks(): void
    {
        $this->dispatcher->before(static fn (): null => null);

        $this->assertTrue($this->call('hasCallbacks', [static::HOOK_BEFORE, static::EVENT, static::PAYLOAD]),
            'The wildcard before callback should be detected for the event; Dispatcher::hasCallbacks() should return true.');
    }

    public function test_callback_detection_recognizes_event_specific_callbacks(): void
    {
        $this->dispatcher->before(static::EVENT, static fn (): null => null);

        $this->assertTrue($this->call('hasCallbacks', [static::HOOK_BEFORE, static::EVENT, static::PAYLOAD]),
            'The event-specific before callback should be detected for the event; Dispatcher::hasCallbacks() should return true.');
    }

    public function test_callback_detection_recognizes_hierarchical_parent_callbacks(): void
    {
        $this->dispatcher->before(ParentEvent::class, static fn (): null => null);

        $this->assertTrue($this->call('hasCallbacks', [static::HOOK_BEFORE, ChildEvent::class, [new ChildEvent]]),
            'The hierarchical parent before callback should be detected for the event; Dispatcher::hasCallbacks() should return true.');
    }

    public function test_callback_detection_recognizes_hierarchical_interface_callbacks(): void
    {
        $this->dispatcher->before(EventInterface::class, static fn (): null => null);

        $this->assertTrue($this->call('hasCallbacks', [static::HOOK_BEFORE, ChildEvent::class, [new ChildEvent]]),
            'The hierarchical interface before callback should be detected for the event; Dispatcher::hasCallbacks() should return true.');
    }

    public function test_callback_detection_returns_false_when_none_are_registered(): void
    {
        $this->assertFalse($this->call('hasCallbacks', [static::HOOK_BEFORE, static::INVALID_EVENT, static::PAYLOAD]),
            'No callbacks should be detected for the event; Dispatcher::hasCallbacks() should return false.');
    }

    public function test_callback_detection_recognizes_event_object_callbacks(): void
    {
        $this->assertTrue($this->call('hasCallbacks', [static::HOOK_BEFORE, get_class($event = new EventWithHooks), [$event]]),
            'The event object before callback should be detected for the event; Dispatcher::hasCallbacks() should return true.');
    }

    public function test_callback_detection_ignores_objects_without_hook_methods(): void
    {
        $this->assertFalse($this->call('hasCallbacks', [static::HOOK_BEFORE, get_class($event = new class {}), [$event]]),
            'No callbacks should be detected for the event; Dispatcher::hasCallbacks() should return false.');
    }

    public function test_has_callbacks_utilizes_memoization_correctly(): void
    {
        $this->set('callbacks', []);
        $this->set('cache', []);

        $this->assertFalse(
            $this->call('hasCallbacks', [static::HOOK_BEFORE, static::EVENT, static::PAYLOAD]),
            '$Dispatcher::hasCallbacks() should return false for the given hook/event initially.');
        $this->assertFalse(
            $this->get('cache')['has_callbacks'][static::HOOK_BEFORE.':'.static::EVENT] ?? false,
            "\$Dispatcher::\$cache['has_callbacks'] should reflect false for the given hook/event initially."
        );

        $this->dispatcher->before(static::EVENT, static fn (): string => static::VALID_CALLBACK);

        $this->assertTrue(
            $this->call('hasCallbacks', [static::HOOK_BEFORE, static::EVENT, static::PAYLOAD]),
            'Dispatcher::hasCallbacks() should return true for the hook/event after first callback registration.'
        );
        $this->assertTrue(
            $this->get('cache')['has_callbacks'][static::HOOK_BEFORE.':'.static::EVENT] ?? false,
            "\$Dispatcher::\$cache['has_callbacks'] should reflect true for the hook/event after first callback registration."
        );

        $this->dispatcher->before(static::EVENT, static fn (): string => static::VALID_CALLBACK);

        $this->assertTrue(
            ($this->get('cache')['has_callbacks'] ?: [])[static::HOOK_BEFORE.':'.static::EVENT] ?? false,
            "\$Dispatcher::\$cache['has_callbacks'] should remain true after registering additional callback."
        );

        $this->set('callbacks', []);
        $this->set('cache', []);

        $this->dispatcher->before(static::EVENT, static fn (): string => static::VALID_CALLBACK);

        $this->assertTrue(
            ($this->get('cache')['has_callbacks'] ?: [])[static::HOOK_BEFORE.':'.static::EVENT] ?? false,
            "\$Dispatcher::\$cache['has_callbacks'] should remain true after registering additional callback"
            .'(even though the cache was cleared).'
        );
    }

    public function test_aggregated_callbacks_are_cached(): void
    {
        $this->dispatcher->before(static::EVENT, $callback = static fn (): string => static::VALID_CALLBACK);

        $this->call('aggregateCallbacks', [static::HOOK_BEFORE, static::EVENT, []]);

        $this->assertArrayHasKey(static::HOOK_BEFORE.':'.static::EVENT, $this->get('cache')['aggregated_callbacks'],
            'Dispatcher::$cache should have an aggregated_callbacks entry for the hook/event.');
        $this->assertSame($callback, Arr::first($this->get('cache')['aggregated_callbacks'][static::HOOK_BEFORE.':'.static::EVENT]),
            'The expected aggregated callback(s) for the hook/event should exist in the cache.');
    }

    public function test_aggregated_callbacks_are_read_from_cache(): void
    {
        $this->set('cache', ['aggregated_callbacks' => [static::HOOK_BEFORE.':'.static::EVENT => [static fn (): string => static::VALID_CALLBACK]]]);

        $callbacks = $this->call('aggregateCallbacks', [static::HOOK_BEFORE, static::EVENT, []]);

        $this->assertSame($callbacks, $this->get('cache')['aggregated_callbacks'][static::HOOK_BEFORE.':'.static::EVENT],
            'The Dispatcher::aggregateCallbacks() method should return the expected callback(s) from the cache.');
    }

    public function test_prepared_callbacks_are_cached(): void
    {
        $this->dispatcher->before(static::EVENT, static fn (): string => static::WILDCARD);

        $this->call('prepareCallbacks', [static::HOOK_BEFORE, static::EVENT, []]);

        $this->assertArrayHasKey(static::HOOK_BEFORE.':'.static::EVENT, $this->get('cache')['prepared_callbacks'],
            'Dispatcher::$cache should have a prepared_callbacks key for the hook/event.');
        $this->assertSame(static::WILDCARD, Arr::first($this->get('cache')['prepared_callbacks'][static::HOOK_BEFORE.':'.static::EVENT])(),
            'The expected prepared callback(s) for the hook/event should exist in the cache.');
    }

    public function test_prepared_callbacks_are_read_from_cache(): void
    {
        $this->set('cache', ['prepared_callbacks' => [static::HOOK_BEFORE.':'.static::EVENT => [static fn (): string => static::WILDCARD]]]);

        $callbacks = $this->call('prepareCallbacks', [static::HOOK_BEFORE, static::EVENT, []]);

        $this->assertSame($callbacks, $this->get('cache')['prepared_callbacks'][static::HOOK_BEFORE.':'.static::EVENT],
            'The Dispatcher::prepareCallbacks() method should return the expected callback(s) from the cache.');
    }

    public function test_ordered_callbacks_are_cached(): void
    {
        $callbacks = [
            static fn (): string => static::VALID_CALLBACK.'1',
            static fn (): string => static::VALID_CALLBACK.'2',
        ];

        $this->call('orderCallbacks', [static::HOOK_BEFORE, static::EVENT, $callbacks]);

        $this->assertArrayHasKey(static::HOOK_BEFORE.':'.static::EVENT, $this->get('cache')['ordered_callbacks'],
            'Dispatcher::$cache should have an ordered_callbacks entry for the hook/event.');
        $this->assertSame($callbacks, $this->get('cache')['ordered_callbacks'][static::HOOK_BEFORE.':'.static::EVENT],
            'The expected ordered callback(s) for the hook/event should exist in the cache.');
    }

    public function test_ordered_callbacks_are_read_from_cache(): void
    {
        $this->set('cache', ['ordered_callbacks' => [static::HOOK_BEFORE.':'.static::EVENT => [static fn (): string => static::VALID_CALLBACK]]]);

        $callbacks = $this->call('orderCallbacks', [static::HOOK_BEFORE, static::EVENT, []]);

        $this->assertSame($callbacks, $this->get('cache')['ordered_callbacks'][static::HOOK_BEFORE.':'.static::EVENT],
            'The Dispatcher::orderCallbacks() method should return the expected callback(s) from the cache.');
    }

    public function test_hook_and_event_callbacks_are_read_from_cache(): void
    {
        $this->set('cache', ['hook_and_event_callbacks' => [static::HOOK_BEFORE.':'.static::EVENT => [static::EVENT => [static fn (): string => static::VALID_CALLBACK]]]]);

        $callbacks = $this->call('callbacksForHookAndEvent', [static::HOOK_BEFORE, static::EVENT]);

        $this->assertSame($callbacks, $this->get('cache')['hook_and_event_callbacks'][static::HOOK_BEFORE.':'.static::EVENT],
            'The Dispatcher::callbacksForHookAndEvent() method should return the expected callback(s) from the cache.');
    }

    public function test_hook_and_event_callbacks_are_cached(): void
    {
        $this->dispatcher->before(static::EVENT, static fn (): string => static::VALID_CALLBACK);

        $this->call('callbacksForHookAndEvent', [static::HOOK_BEFORE, static::EVENT]);

        $this->assertArrayHasKey(static::EVENT, $this->get('cache')['hook_and_event_callbacks'][static::HOOK_BEFORE.':'.static::EVENT],
            'Dispatcher::$cache should have a hook_and_event_callbacks entry for the hook/event.');
        $this->assertSame(static::VALID_CALLBACK, Arr::first($this->get('cache')['hook_and_event_callbacks'][static::HOOK_BEFORE.':'.static::EVENT][static::EVENT])(),
            'The expected callback(s) for the hook/event should exist in the cache.');
    }

    public function test_event_callbacks_are_read_from_cache(): void
    {
        $this->set('cache', ['event_callbacks' => [static::EVENT => [static::HOOK_BEFORE => [static::EVENT => [static fn (): string => static::VALID_CALLBACK]]]]]);

        $callbacks = $this->call('callbacksForEvent', [static::EVENT]);

        $this->assertSame($callbacks, $this->get('cache')['event_callbacks'][static::EVENT],
            'The Dispatcher::callbacksForEvent() method should return the expected callback(s) from the cache.');
    }

    public function test_event_callbacks_are_cached(): void
    {
        $this->dispatcher->before(static::EVENT, static fn (): string => static::VALID_CALLBACK);

        $this->call('callbacksForEvent', [static::EVENT]);

        $this->assertArrayHasKey(static::EVENT, $this->get('cache')['event_callbacks'],
            'Dispatcher::$cache should have a event_callbacks entry for the hook/event.');

        $this->assertSame(static::VALID_CALLBACK, Arr::first($this->get('cache')['event_callbacks'][static::EVENT][static::HOOK_BEFORE][static::EVENT])(),
            'The expected callback(s) for the hook/event should exist in the cache.');
    }

    public function test_event_hierarchies_are_cached(): void
    {
        $this->call('eventHierarchy', [EventWithHooks::class]);

        $this->assertArrayHasKey('event_hierarchies', $this->get('cache'),
            'Dispatcher::$cache should have an event_hierarchies entry for the event.');
        $this->assertSame(EventWithHooks::class, Arr::first($this->get('cache')['event_hierarchies'][EventWithHooks::class]),
            'The expected hierarchical class(es) for the event should exist in the cache.');
    }

    public function test_event_hierarchies_are_read_from_cache(): void
    {
        $this->set('cache', ['event_hierarchies' => [EventWithHooks::class => ['cached']]]);

        $hierarchy = $this->call('eventHierarchy', [EventWithHooks::class]);

        $this->assertSame($hierarchy, $this->get('cache')['event_hierarchies'][EventWithHooks::class],
            'The Dispatcher::eventHierarchy() method should return the expected hierarchy from the cache.');
    }

    public function test_hierarchical_callbacks_are_cached(): void
    {
        $this->dispatcher->before(static::EVENT, static fn (): string => static::WILDCARD);

        $this->call('aggregateHierarchicalCallbacks', [static::HOOK_BEFORE, static::EVENT, []]);

        $this->assertArrayHasKey(static::HOOK_BEFORE.':'.static::EVENT, $this->get('cache')['hierarchical_callbacks'],
            'Dispatcher::$cache should have a hierarchical_callbacks entry for the hook/event.');
        $this->assertSame(static::WILDCARD, Arr::first($this->get('cache')['hierarchical_callbacks'][static::HOOK_BEFORE.':'.static::EVENT])(),
            'The expected aggregated hierarchical callback(s) for the hook/event should exist in the cache.');
    }

    public function test_hierarchical_callbacks_are_read_from_cache(): void
    {
        $this->set('cache', ['hierarchical_callbacks' => [static::HOOK_BEFORE.':'.static::EVENT => [static fn (): string => static::WILDCARD]]]);

        $callbacks = $this->call('aggregateHierarchicalCallbacks', [static::HOOK_BEFORE, static::EVENT, []]);

        $this->assertSame($callbacks, $this->get('cache')['hierarchical_callbacks'][static::HOOK_BEFORE.':'.static::EVENT],
            'The Dispatcher::aggregateHierarchicalCallbacks() method should return the expected callback(s) from the cache.');
    }

    private function call(string $method, array $args = []): mixed
    {
        return tap(
            new ReflectionMethod($this->dispatcher, $method),
            static function (ReflectionMethod $method): void {
                $method->setAccessible(true);
            }
        )->invokeArgs($this->dispatcher, $args);
    }

    private function get(string $property): mixed
    {
        return tap(
            (new ReflectionClass($this->dispatcher))->getProperty($property),
            static function ($property): void {
                $property->setAccessible(true);
            }
        )->getValue($this->dispatcher);
    }

    private function set(string $property, $value): void
    {
        tap(
            new ReflectionProperty($this->dispatcher, $property),
            static function (ReflectionProperty $property): void {
                $property->setAccessible(true);
            }
        )->setValue($this->dispatcher, $value);
    }

    public const HOOK_BEFORE = 'before';

    public const HOOK_AFTER = 'after';

    public const HOOK_FAILURE = 'failure';

    private const WILDCARD = '*';

    private const EVENT = 'event';

    private const INVALID_EVENT = 'invalid';

    private const PAYLOAD = ['payload'];

    private const LISTENER = 'listener';

    private const INVALID_HOOK = 'invalid';

    private const VALID_CALLBACK = 'callback';

    private const INVALID_CALLBACK = 'invalid';

    private const INVALID_CLASS = 'invalid';

    private const VALID_METHOD = 'method';

    private const INVALID_METHOD = 'invalid';
}

class TestDispatcher extends Dispatcher
{
    public array $invoked = [];

    public bool $throw = false;

    protected function invokeCallbacks(string $hook, string $event, array $payload): void
    {
        if ($this->throw && $hook === self::HOOK_BEFORE) {
            throw new EventPropagationException;
        }

        $this->invoked[] = ['hook' => $hook, 'event' => $event, 'payload' => $payload];

        parent::invokeCallbacks($hook, $event, $payload);
    }
}

class MethodCallbackClass
{
    public string $payload = '';

    public function handle(string $payload): void
    {
        $this->payload = $payload;
    }

    public function method(string $payload): void
    {
        $this->payload = $payload;
    }
}

class InvokableCallbackClass
{
    public string $payload = '';

    public function __invoke(string $payload): void
    {
        $this->payload = $payload;
    }
}

class EventWithHooks
{
    public bool $beforeCalled = false;

    public bool $afterCalled = false;

    public bool $failureCalled = false;

    public array $beforePayload = [];

    public array $afterPayload = [];

    public array $failurePayload = [];

    public mixed $callback = null;

    public function before(...$args): void
    {
        $this->beforeCalled = true;
        $this->beforePayload = $args;

        if ($this->callback) {
            call_user_func($this->callback, EventHooksTest::HOOK_BEFORE);
        }
    }

    public function after(...$args): void
    {
        $this->afterCalled = true;
        $this->afterPayload = $args;

        if ($this->callback) {
            call_user_func($this->callback, EventHooksTest::HOOK_AFTER);
        }
    }

    public function failure(...$args): void
    {
        $this->failureCalled = true;
        $this->failurePayload = $args;

        if ($this->callback) {
            call_user_func($this->callback, EventHooksTest::HOOK_FAILURE);
        }
    }
}
interface EventInterface {}

abstract class ParentEvent implements EventInterface {}

class ChildEvent extends ParentEvent {}
