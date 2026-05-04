<?php

namespace Illuminate\Tests\Bus;

use Illuminate\Bus\Events\StepCompleted;
use Illuminate\Bus\Events\StepFailed;
use Illuminate\Bus\Events\StepStarting;
use Illuminate\Bus\ExecutionContext\CacheExecutionRepository;
use Illuminate\Bus\ExecutionContext\ExecutionContext;
use Illuminate\Bus\ExecutionContext\ExecutionState;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository as CacheRepository;
use Illuminate\Container\Container;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Workflow\ExecutionRepository;
use Illuminate\Events\Dispatcher as BaseEventDispatcher;
use Illuminate\Support\Carbon;
use Illuminate\Support\Testing\Fakes\EventFake;
use PHPUnit\Framework\TestCase;
use RuntimeException;

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

        $events = $this->fakeEvents();
        $repository = new ExecutionContextRecordingExecutionRepository;
        $state = new ExecutionState('execution-1');
        $context = new ExecutionContext($repository, $events, $state);

        $result = $context->step('fetch-products', static fn () => ['product-1', 'product-2']);

        $this->assertSame(['product-1', 'product-2'], $result);
        $events->assertDispatched(StepStarting::class, function (StepStarting $event) use ($state) {
            return $event->state === $state
                && $event->step === 'fetch-products';
        });
        $events->assertDispatched(StepCompleted::class, function (StepCompleted $event) use ($now, $state) {
            return $event->state === $state
                && $event->step === 'fetch-products'
                && $event->result === ['product-1', 'product-2']
                && $event->completedAt === $now->getTimestamp();
        });
        $this->assertSame([
            ['state' => $state, 'name' => 'fetch-products', 'ttl' => null],
        ], $repository->savedSteps);
    }

    public function testStepReturnsStoredResultWhenAlreadyCompleted()
    {
        $events = $this->fakeEvents();
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
        $events->assertDispatchedTimes(StepStarting::class, 1);
        $events->assertDispatchedTimes(StepCompleted::class, 1);
        $this->assertCount(1, $repository->savedSteps);
    }

    public function testStepDispatchesFailedEventWhenCallbackThrows()
    {
        $events = $this->fakeEvents();
        $repository = new ExecutionContextRecordingExecutionRepository;
        $state = new ExecutionState('execution-1');
        $context = new ExecutionContext($repository, $events, $state);
        $exception = new RuntimeException('Unable to fetch products.');

        try {
            $context->step('fetch-products', static fn () => throw $exception);

            $this->fail('The step callback exception was not thrown.');
        } catch (RuntimeException $e) {
            $this->assertSame($exception, $e);
        }

        $events->assertDispatched(StepFailed::class, function (StepFailed $event) use ($exception, $state) {
            return $event->state === $state
                && $event->step === 'fetch-products'
                && $event->exception === $exception;
        });
        $events->assertNotDispatched(StepCompleted::class);
        $this->assertSame([], $repository->savedSteps);
    }

    public function testStepPassesTtlOptionToRepository()
    {
        $repository = new ExecutionContextRecordingExecutionRepository;
        $state = new ExecutionState('execution-1');
        $context = new ExecutionContext($repository, null, $state);

        $result = $context->step('fetch-products', static fn () => 'new-result', ['ttl' => 300]);

        $this->assertSame('new-result', $result);
        $this->assertSame([
            ['state' => $state, 'name' => 'fetch-products', 'ttl' => 300],
        ], $repository->savedSteps);
    }

    public function testStepUsesStateLoadedFromRepository()
    {
        $events = $this->fakeEvents();
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
        $events->assertNothingDispatched();
    }

    public function testConstructorCreatesStateWhenRepositoryDoesNotFindOne()
    {
        $events = $this->fakeEvents();
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

    protected function fakeEvents(): EventFake
    {
        return new EventFake(new BaseEventDispatcher(new Container));
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
