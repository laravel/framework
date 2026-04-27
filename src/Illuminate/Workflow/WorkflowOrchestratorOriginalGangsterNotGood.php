<?php

declare(strict_types=1);

namespace Illuminate\Workflow;

use Attribute;
use Closure;
use Fiber;
use LogicException;
use ReflectionClass;
use ReflectionMethod;
use Throwable;

use function array_key_exists;
use function array_values;
use function class_basename;
use function usort;

class WorkflowOrchestratorOriginalGangsterNotGood
{
    /**
     * @var \Fiber<mixed, mixed, mixed, mixed>
     */
    protected Fiber $fiber;

    /**
     * @var array<string, mixed>
     */
    protected array $state;

    /**
     * @var array{status: string, workflow: string, step: string|null, payload: mixed, state: array<string, mixed>}|null
     */
    protected ?array $lastSnapshot = null;

    protected mixed $result = null;

    protected bool $started = false;

    protected bool $completed = false;

    protected ?Closure $persistState;

    protected string $name;

    public function __construct(
        protected object $workflow,
        array $state = [],
        ?callable $persistState = null,
    ) {
        $this->state = $state;
        $this->persistState = $persistState === null ? null : Closure::fromCallable($persistState);
        $this->name = $this->resolveWorkflowName();
        $this->fiber = new Fiber(fn () => $this->runSteps());
    }

    public static function make(object $workflow, array $state = [], ?callable $persistState = null): static
    {
        return new static($workflow, $state, $persistState);
    }

    /**
     * Run until the next step completes, or until the workflow finishes.
     *
     * @return array{status: string, workflow: string, step: string|null, payload: mixed, state: array<string, mixed>}
     */
    public function run(): array
    {
        if ($this->completed) {
            return $this->completionSnapshot();
        }

        try {
            $snapshot = $this->started
                ? $this->fiber->resume()
                : $this->fiber->start();
        } catch (Throwable $e) {
            $this->persist($this->snapshot('failed', null, [
                'class' => $e::class,
                'message' => $e->getMessage(),
            ]));

            throw $e;
        }

        $this->started = true;

        if ($this->fiber->isTerminated()) {
            $this->completed = true;
            $this->result = $this->fiber->getReturn();

            return $this->persist($this->completionSnapshot());
        }

        return $snapshot;
    }

    /**
     * @return array<string, mixed>
     */
    public function state(): array
    {
        return $this->state;
    }

    public function result(): mixed
    {
        return $this->result;
    }

    public function completed(): bool
    {
        return $this->completed;
    }

    /**
     * @return array{status: string, workflow: string, step: string|null, payload: mixed, state: array<string, mixed>}|null
     */
    public function lastSnapshot(): ?array
    {
        return $this->lastSnapshot;
    }

    protected function runSteps(): mixed
    {
        foreach ($this->steps() as $step) {
            if (array_key_exists($step['name'], $this->state)) {
                continue;
            }

            $this->state[$step['name']] = $step['method']->invokeArgs(
                $this->workflow,
                $this->argumentsFor($step['method']),
            );

            Fiber::suspend($this->persist($this->snapshot(
                'step_completed',
                $step['name'],
                $this->state[$step['name']],
            )));
        }

        return $this->state;
    }

    /**
     * @return list<array{order: int, name: string, method: \ReflectionMethod}>
     */
    protected function steps(): array
    {
        $steps = [];

        foreach ((new ReflectionClass($this->workflow))->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            $attributes = $method->getAttributes(WorkflowStep::class);

            if ($attributes === []) {
                continue;
            }

            $attribute = $attributes[0]->newInstance();

            $steps[] = [
                'order' => $attribute->order,
                'name' => $attribute->name ?? $method->getName(),
                'method' => $method,
            ];
        }

        usort($steps, fn (array $first, array $second): int => $first['order'] <=> $second['order']);

        return array_values($steps);
    }

    /**
     * @return list<mixed>
     */
    protected function argumentsFor(ReflectionMethod $method): array
    {
        $arguments = [];

        foreach ($method->getParameters() as $parameter) {
            if (array_key_exists($parameter->getName(), $this->state)) {
                $arguments[] = $this->state[$parameter->getName()];

                continue;
            }

            if ($parameter->isDefaultValueAvailable()) {
                $arguments[] = $parameter->getDefaultValue();

                continue;
            }

            throw new LogicException('Unable to resolve workflow step parameter [$'.$parameter->getName().'].');
        }

        return $arguments;
    }

    protected function resolveWorkflowName(): string
    {
        $reflection = new ReflectionClass($this->workflow);
        $attributes = $reflection->getAttributes(WorkflowName::class);

        if ($attributes !== []) {
            return $attributes[0]->newInstance()->name;
        }

        return class_basename($this->workflow);
    }

    /**
     * @return array{status: string, workflow: string, step: string|null, payload: mixed, state: array<string, mixed>}
     */
    protected function completionSnapshot(): array
    {
        return $this->snapshot('completed', null, $this->result);
    }

    /**
     * @return array{status: string, workflow: string, step: string|null, payload: mixed, state: array<string, mixed>}
     */
    protected function snapshot(string $status, ?string $step, mixed $payload = null): array
    {
        return [
            'status' => $status,
            'workflow' => $this->name,
            'step' => $step,
            'payload' => $payload,
            'state' => $this->state,
        ];
    }

    /**
     * @param  array{status: string, workflow: string, step: string|null, payload: mixed, state: array<string, mixed>}  $snapshot
     * @return array{status: string, workflow: string, step: string|null, payload: mixed, state: array<string, mixed>}
     */
    protected function persist(array $snapshot): array
    {
        $this->lastSnapshot = $snapshot;

        if ($this->persistState !== null) {
            ($this->persistState)($snapshot, $this);
        }

        return $snapshot;
    }
}

#[Attribute(Attribute::TARGET_CLASS)]
class WorkflowName
{
    public function __construct(public string $name)
    {
    }
}

#[Attribute(Attribute::TARGET_METHOD)]
class WorkflowStep
{
    public function __construct(public int $order, public ?string $name = null)
    {
    }
}
