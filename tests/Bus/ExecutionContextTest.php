<?php

namespace Illuminate\Tests\Bus;

use Illuminate\Bus\Events\StepCompleted;
use Illuminate\Bus\Events\StepStarting;
use Illuminate\Bus\ExecutionContext\CacheExecutionRepository;
use Illuminate\Bus\ExecutionContext\ExecutionContext;
use Illuminate\Bus\ExecutionContext\ExecutionState;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;
use Illuminate\Contracts\Workflow\ExecutionRepository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Bus;
use PHPUnit\Framework\TestCase;

class ExecutionContextTest extends TestCase
{
    #[\Override]
    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function testStepReturnsResultAndDispatchesLifecycleEvents()
    {
        Carbon::setTestNow($now = Carbon::parse('2026-04-01 21:53:00'));

        $events = new ExecutionContextRecordingEventDispatcher;
        $repository = new ExecutionContextRecordingExecutionRepository;
        $state = new ExecutionState('execution-1');
        $context = new ExecutionContext($repository, $events, $state);

        $result = $context->step('fetch-products', static fn () => ['product-1', 'product-2']);

        $this->assertSame(['product-1', 'product-2'], $result);
        $this->assertCount(2, $events->events);
        $this->assertInstanceOf(StepStarting::class, $events->events[0]);
        $this->assertSame($state, $events->events[0]->state);
        $this->assertSame('fetch-products', $events->events[0]->step);
        $this->assertInstanceOf(StepCompleted::class, $events->events[1]);
        $this->assertSame($state, $events->events[1]->state);
        $this->assertSame('fetch-products', $events->events[1]->step);
        $this->assertSame(['product-1', 'product-2'], $events->events[1]->result);
        $this->assertSame($now->getTimestamp(), $events->events[1]->completedAt);
        $this->assertSame([
            ['state' => $state, 'name' => 'fetch-products', 'ttl' => null],
        ], $repository->savedSteps);
    }

    public function testStepReturnsStoredResultWhenAlreadyCompleted()
    {
        $events = new ExecutionContextRecordingEventDispatcher;
        $repository = new ExecutionContextRecordingExecutionRepository;
        $state = new ExecutionState('execution-1');
        $context = new ExecutionContext($repository, $events, $state);
        $runs = 0;

        $firstResult = $context->step('fetch-products', static function () use (&$runs) {
            $runs++;

            return 'first-result';
        });

        $secondResult = $context->step('fetch-products', static function () use (&$runs) {
            $runs++;

            return 'second-result';
        });

        $this->assertSame('first-result', $firstResult);
        $this->assertSame('first-result', $secondResult);
        $this->assertSame(1, $runs);
        $this->assertCount(2, $events->events);
        $this->assertCount(1, $repository->savedSteps);
    }

    public function testStepUsesStateLoadedFromRepository()
    {
        $events = new ExecutionContextRecordingEventDispatcher;
        $state = new ExecutionState('execution-1');
        $state->recordStepResult('fetch-products', 'stored-result', 123);
        $repository = new ExecutionContextRecordingExecutionRepository($state);
        $context = new ExecutionContext($repository, $events, 'execution-1');
        $runs = 0;

        $result = $context->step('fetch-products', static function () use (&$runs) {
            $runs++;

            return 'new-result';
        });

        $this->assertSame('stored-result', $result);
        $this->assertSame(0, $runs);
        $this->assertSame(['execution-1'], $repository->finds);
        $this->assertSame([], $repository->creates);
        $this->assertSame([], $repository->savedSteps);
        $this->assertSame([], $events->events);
    }

    public function testConstructorCreatesStateWhenRepositoryDoesNotFindOne()
    {
        $events = new ExecutionContextRecordingEventDispatcher;
        $repository = new ExecutionContextRecordingExecutionRepository;
        $context = new ExecutionContext($repository, $events, 'execution-1');

        $result = $context->step('fetch-products', static fn () => 'new-result');

        $this->assertSame('new-result', $result);
        $this->assertSame(['execution-1'], $repository->finds);
        $this->assertCount(1, $repository->creates);
        $this->assertSame('execution-1', $repository->creates[0]['id']);
        $this->assertNull($repository->creates[0]['ttl']);
        $this->assertCount(1, $repository->savedSteps);
        $this->assertSame('execution-1', $repository->savedSteps[0]['state']->id());
    }

    public function testContextCanCreateStateUsingCacheRepository()
    {
        Carbon::setTestNow($now = Carbon::parse('2026-04-01 21:53:00'));

        $repository = new CacheExecutionRepository(
            new ExecutionContextCacheFactory(new CacheRepository(new ArrayStore))
        );

        $context = new ExecutionContext($repository, null, 'execution-1');

        $result = $context->step('fetch-products', static fn () => 'new-result');
        $stored = $repository->find('execution-1');

        $this->assertSame('new-result', $result);
        $this->assertInstanceOf(ExecutionState::class, $stored);
        $this->assertSame('execution-1', $stored->id());
        $this->assertSame([
            'fetch-products' => [
                'completed_at' => $now->getTimestamp(),
                'result' => 'new-result',
            ],
        ], $stored->all());
    }
}

class ExecutionContextCacheFactory implements CacheFactory
{
    public function __construct(
        protected CacheRepository $repository,
    ) {
    }

    public function store($name = null)
    {
        return $this->repository;
    }
}

class ExecutionContextRecordingExecutionRepository implements ExecutionRepository
{
    public array $creates = [];

    public array $deletes = [];

    public array $finds = [];

    public array $savedSteps = [];

    public array $states = [];

    public function __construct(ExecutionState ...$states)
    {
        foreach ($states as $state) {
            $this->states[$state->id()] = $state;
        }
    }

    public function find(mixed $id)
    {
        $this->finds[] = $id;

        return $this->states[$id] ?? null;
    }

    public function create(mixed $id, $ttl = null)
    {
        $this->creates[] = ['id' => $id, 'ttl' => $ttl];

        $state = $id instanceof ExecutionState ? $id : new ExecutionState($id);
        $this->states[$state->id()] = $state;

        return $state;
    }

    public function saveStep($state, string $name, $ttl = null): void
    {
        $this->savedSteps[] = ['state' => $state, 'name' => $name, 'ttl' => $ttl];
        $this->states[$state->id()] = $state;
    }

    public function delete($id): void
    {
        $this->deletes[] = $id;

        unset($this->states[$id instanceof ExecutionState ? $id->id() : $id]);
    }
}

class ExecutionContextRecordingEventDispatcher implements EventDispatcher
{
    public array $events = [];

    public function listen($events, $listener = null)
    {
        //
    }

    public function hasListeners($eventName)
    {
        return false;
    }

    public function subscribe($subscriber)
    {
        //
    }

    public function until($event, $payload = [])
    {
        return null;
    }

    public function dispatch($event, $payload = [], $halt = false)
    {
        $this->events[] = $event;

        return null;
    }

    public function push($event, $payload = [])
    {
        //
    }

    public function flush($event)
    {
        //
    }

    public function forget($event)
    {
        //
    }

    public function forgetPushed()
    {
        //
    }
}
