<?php

namespace Illuminate\Support\Testing\Fakes;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Auth\Access\Gate as GateContract;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Traits\ForwardsCalls;
use Illuminate\Support\Traits\ReflectsClosures;
use PHPUnit\Framework\Assert as PHPUnit;
use Throwable;

class GateFake implements Fake, GateContract
{
    use ForwardsCalls, ReflectsClosures;

    /**
     * The original Gate implementation.
     */
    public readonly GateContract $gate;

    /**
     * The abilities that should be faked.
     */
    protected array $fake = [];

    /**
     * The abilities that should be dispatched normally.
     */
    protected array $dispatch = [];

    /**
     * All the abilities that have been checked.
     */
    protected array $checked = [];

    /**
     * The call order counter for tracking sequences.
     */
    protected int $sequence = 0;

    /**
     * Create a new Gate fake instance.
     *
     * @param  array<int, string>  $fake
     */
    public function __construct(GateContract $gate, array $fake = [])
    {
        $this->gate = $gate;
        $this->fake = array_map('strtolower', Arr::wrap($fake));
    }

    /**
     * Determine if a given ability has been granted.
     *
     * @param  string  $ability
     * @param  array<int, mixed>  $arguments
     */
    public function allows($ability, $arguments = []): bool
    {
        $ability = strtolower($ability);

        if (! $this->shouldFake($ability)) {
            return $this->gate->allows($ability, $arguments);
        }

        $this->record($ability, $arguments, true);

        return true;
    }

    /**
     * Determine if a given ability has been denied.
     *
     * @param  string  $ability
     * @param  array<int, mixed>  $arguments
     */
    public function denies($ability, $arguments = []): bool
    {
        $ability = strtolower($ability);

        if (! $this->shouldFake($ability)) {
            return $this->gate->denies($ability, $arguments);
        }

        $this->record($ability, $arguments, false);

        return false;
    }

    /**
     * Determine if all the given abilities should be granted.
     *
     * @param  array<int, string>|string  $abilities
     * @param  array<int, mixed>  $arguments
     */
    public function check($abilities, $arguments = []): bool
    {
        if (empty($abilities)) {
            return true;
        }

        return collect($abilities)
            ->every(fn ($ability): bool => $this->allows($ability, $arguments));
    }

    /**
     * Determine if any one of the given abilities should be granted.
     *
     * @param  array<int, string>|string  $abilities
     * @param  array<int, mixed>  $arguments
     */
    public function any($abilities, $arguments = []): bool
    {
        return collect(Arr::wrap($abilities))
            ->first(fn ($ability): bool => $this->allows($ability, $arguments)) !== null;
    }

    /**
     * Determine if the given ability should be granted for the current user.
     *
     * @param  string  $ability
     * @param  array<int, mixed>  $arguments
     *
     * @throws AuthorizationException
     */
    public function authorize($ability, $arguments = []): Response
    {
        $ability = strtolower($ability);

        if (! $this->shouldFake($ability)) {
            return $this->gate->authorize($ability, $arguments);
        }

        $this->record($ability, $arguments, true);

        return new Response(true);
    }

    /**
     * Inspect the user for the given ability.
     *
     * @param  string  $ability
     * @param  array<int, mixed>  $arguments
     */
    public function inspect($ability, $arguments = []): Response
    {
        $ability = strtolower($ability);

        if (! $this->shouldFake($ability)) {
            return $this->gate->inspect($ability, $arguments);
        }

        $this->record($ability, $arguments, true);

        return new Response(true);
    }

    /**
     * Get the raw result from the authorization callback.
     *
     * @param  string  $ability
     * @param  array<int, mixed>  $arguments
     *
     * @throws AuthorizationException
     */
    public function raw($ability, $arguments = []): mixed
    {
        $ability = strtolower($ability);

        if (! $this->shouldFake($ability)) {
            return $this->gate->raw($ability, $arguments);
        }

        $this->record($ability, $arguments, true);

        return true;
    }

    /**
     * Determine if a given ability has been defined.
     *
     * @param  string  $ability
     */
    public function has($ability): bool
    {
        return $this->gate->has(strtolower($ability));
    }

    /**
     * Define a new ability.
     *
     * @param  string  $ability
     * @param  callable  $callback
     */
    public function define($ability, $callback): GateContract
    {
        return $this->gate->define(strtolower($ability), $callback);
    }

    /**
     * Define abilities for a resource.
     *
     * @param  string  $name
     * @param  string  $class
     * @param  array<int, string>|null  $abilities
     */
    public function resource($name, $class, ?array $abilities = null): GateContract
    {
        return $this->gate->resource($name, $class, $abilities);
    }

    /**
     * Define a policy class for a given class type.
     *
     * @param  string  $class
     * @param  string  $policy
     */
    public function policy($class, $policy): GateContract
    {
        return $this->gate->policy($class, $policy);
    }

    /**
     * Register a callback to run before all Gate checks.
     */
    public function before(callable $callback): GateContract
    {
        return $this->gate->before($callback);
    }

    /**
     * Register a callback to run after all Gate checks.
     */
    public function after(callable $callback): GateContract
    {
        return $this->gate->after($callback);
    }

    /**
     * Get a policy instance for a given class.
     *
     * @param  string  $class
     */
    public function getPolicyFor($class): mixed
    {
        return $this->gate->getPolicyFor($class);
    }

    /**
     * Get a guard instance for the given user.
     *
     * @param  Authenticatable|mixed  $user
     */
    public function forUser($user): GateContract
    {
        return $this->gate->forUser($user);
    }

    /**
     * Get all the defined abilities.
     */
    public function abilities(): array
    {
        return $this->gate->abilities();
    }

    /**
     * Get all the abilities that have been checked.
     *
     * @param  callable(string $ability, array $arguments): bool|int|null  $callback
     * @return Collection<int, array{ability: string, arguments: array, result: bool, order: int, timestamp: float}>
     */
    public function checked(string $ability, callable|int|null $callback = null): Collection
    {
        return $this->checkAbility(strtolower($ability), $callback ?: static fn (): true => true);
    }

    /**
     * Determine if the given ability has been checked.
     */
    public function hasChecked(string $ability): bool
    {
        return isset($this->checked[strtolower($ability)]);
    }

    /**
     * Specify the abilities that should be dispatched instead of faked.
     *
     * @param  string|array<int, string>  $abilities
     */
    public function except(string|array $abilities): static
    {
        return tap(
            $this,
            function () use ($abilities): void {
                $this->dispatch = array_merge(
                    $this->dispatch,
                    array_map('strtolower', Arr::wrap($abilities))
                );
            }
        );
    }

    /**
     * Assert that the given ability was checked.
     */
    public function assertChecked(string $ability, callable|int|null $callback = null): void
    {
        is_numeric($callback)
            ? $this->assertCheckedTimes($ability, $callback)
            : PHPUnit::assertTrue(
                $this->checked($ability, $callback)->count() > 0,
                'The expected ability ['.strtolower($ability).'] was not checked. '.
                'Checked abilities: ['.implode(', ', array_keys($this->checked)).'].'
            );
    }

    /**
     * Assert that the given ability was not checked.
     */
    public function assertNotChecked(string $ability, ?callable $callback = null): void
    {
        PHPUnit::assertEquals(
            0,
            $this->checked($ability, $callback)->count(),
            'The unexpected ability ['.strtolower($ability).'] was checked. '.
            'Checked abilities: ['.implode(', ', array_keys($this->checked)).'].'
        );
    }

    /**
     * Assert that no abilities were checked.
     */
    public function assertNothingChecked(): void
    {
        PHPUnit::assertEmpty(
            $this->checked,
            'Abilities were checked unexpectedly.'
        );
    }

    /**
     * Assert that the given ability was checked the given number of times.
     */
    public function assertCheckedTimes(string $ability, int $times = 1): void
    {
        PHPUnit::assertSame(
            $times, $count = $this->checked($ability)->count(),
            'The expected ability ['.strtolower($ability)."] was checked {$count} times instead of {$times} times. ".
            'Checked abilities: ['.implode(', ', array_keys($this->checked)).'].'
        );
    }

    /**
     * Assert that the given ability was checked with specific arguments.
     */
    public function assertCheckedWith(string $ability, mixed ...$arguments): void
    {
        $normalized = strtolower($ability);

        $hits = collect($this->checked[$normalized] ?? [])
            ->filter(function (array $call) use ($arguments) {
                return $this->argumentsMatch($call['arguments'], $arguments);
            });

        PHPUnit::assertTrue(
            $hits->isNotEmpty(),
            "The expected ability [{$normalized}] was not checked with the given arguments. ".
            'Checked abilities: ['.implode(', ', array_keys($this->checked)).'].'
        );
    }

    /**
     * Assert that the given abilities were checked in the specified order.
     *
     * @param  array<int, string>  $abilities
     */
    public function assertCheckedInOrder(array $abilities): void
    {
        $checked = collect($this->checked)
            ->flatMap(
                static fn ($records, $ability): Collection => collect($records)
                    ->map(static fn ($record): array => ['ability' => $ability, 'order' => $record['order']])
            )
            ->sortBy('order')
            ->pluck('ability')
            ->toArray();

        $indices = collect(array_map('strtolower', $abilities))
            ->map(
                static function (string $ability) use ($checked): int {
                    if (($index = array_search($ability, $checked, true)) === false) {
                        PHPUnit::fail("The expected ability [{$ability}] was not checked.");
                    }

                    return $index;
                }
            );

        PHPUnit::assertSame(
            $indices->toArray(),
            $indices->sort()->values()->toArray(),
            'The abilities were not checked in the expected order. '.
            'Expected: ['.implode(', ', $abilities).'], '.
            'Actual: ['.implode(', ', $checked).'].'
        );
    }

    /**
     * Assert that abilities were checked for the given user.
     */
    public function assertCheckedForUser(object $user, ?string $ability = null): void
    {
        $calls = collect($this->checked)
            ->when($ability !== null,
                static fn ($collection): Collection => $collection->only([strtolower($ability)])
            )
            ->flatMap(static fn ($records): Collection => collect($records))
            ->filter(function (array $call) use ($user): bool {
                return (($caller = $this->extractUser($call['arguments'])) !== null)
                    && $this->usersMatch($user, $caller);
            });

        PHPUnit::assertTrue(
            $calls->isNotEmpty(),
            "No abilities were checked for user [{$this->getUserIdentifier($user)}]"
                .($ability !== null ? ' for ability ['.strtolower($ability).']' : '').'.'
        );
    }

    /**
     * Forward another method call to the original Gate instance.
     */
    public function __call(string $method, mixed $parameters): mixed
    {
        return $this->forwardCallTo($this->gate, $method, $parameters);
    }

    /**
     * Determine if the given ability should be faked.
     */
    protected function shouldFake(string $ability): bool
    {
        return match (true) {
            empty($this->fake) && empty($this->dispatch), // everything
            (! empty($this->dispatch)) && (! in_array($ability, $this->dispatch, true)), // except
            (! empty($this->fake)) && in_array($ability, $this->fake, true) => true, // only
            default => false, // nothing
        };
    }

    /**
     * Record a gate call with enhanced details.
     *
     * @param  array<int, mixed>  $arguments
     */
    protected function record(string $ability, array $arguments, bool $result): void
    {
        $this->checked[$ability][] = [
            'arguments' => $arguments,
            'result' => $result,
            'order' => ++$this->sequence,
            'timestamp' => microtime(true),
        ];
    }

    /**
     * Filter the checked ability based on a truth-test callback.
     */
    protected function checkAbility(string $ability, callable $callback): Collection
    {
        return collect($this->checked[$ability] ?? [])
            ->map(fn ($record): array => array_merge($record, ['ability' => $ability]))
            ->filter(
                static function (array $checked) use ($ability, $callback): bool {
                    return $callback($ability, $checked['arguments']);
                }
            );
    }

    /**
     * Determine if the given arguments match the expected arguments.
     *
     * @param  array<int, mixed>  $actuals
     * @param  array<int, mixed>  $expecteds
     */
    protected function argumentsMatch(array $actuals, array $expecteds): bool
    {
        if (count($actuals) !== count($expecteds)) {
            return false;
        }

        return collect($expecteds)
            ->every(
                fn ($expected, $index): bool => array_key_exists($index, $actuals)
                    && $this->valuesMatch($expected, $actuals[$index])
            );
    }

    /**
     * Determine if the given values match the expected values.
     */
    protected function valuesMatch(mixed $expected, mixed $actual): bool
    {
        return match (true) {
            $expected === $actual => true,
            ! (is_object($expected) && is_object($actual)),
            get_class($expected) !== get_class($actual) => false,
            method_exists($expected, 'getKey')
            && method_exists($actual, 'getKey') => $expected->getKey() === $actual->getKey(),
            default => $this->safeCompare($expected, $actual),
        };
    }

    /**
     * Extract user from gate call arguments.
     *
     * @param  array<int, mixed>  $arguments
     */
    protected function extractUser(array $arguments): ?object
    {
        foreach ($arguments as $argument) {
            if ($this->looksLikeUser($argument)) {
                return $argument;
            }
        }

        return null;
    }

    /**
     * Determine if the given value looks like a user object.
     */
    protected function looksLikeUser(mixed $value): bool
    {
        return is_object($value) && match (true) {
            $value instanceof Authenticatable => true,
            default => (bool) count(
                array_intersect(
                    array_keys(get_object_vars($value)),
                    ['id', 'email', 'name', 'username']
                )
            ),
        };
    }

    /**
     * Determine if two users match.
     */
    protected function usersMatch(object $expected, object $actual): bool
    {
        return match (true) {
            $expected === $actual => true,
            $expected instanceof Authenticatable
                && $actual instanceof Authenticatable => $expected->getAuthIdentifier() === $actual->getAuthIdentifier(),
            method_exists($expected, 'getKey')
                && method_exists($actual, 'getKey') => $expected->getKey() === $actual->getKey(),
            default => $this->safeCompare($expected, $actual),
        };
    }

    /**
     * Get a string identifier for the user for error messages.
     */
    protected function getUserIdentifier(object $user): string
    {
        return match (true) {
            $user instanceof Authenticatable => 'ID:'.$user->getAuthIdentifier(),
            method_exists($user, 'getKey') => 'ID:'.$user->getKey(),
            isset($user->id) => 'ID:'.$user->id,
            isset($user->email) => $user->email,
            isset($user->name) => $user->name,
            default => get_class($user),
        };
    }

    /**
     * Safely compare two objects for equality.
     */
    protected function safeCompare(object $expected, object $actual): bool
    {
        try {
            return get_object_vars($expected) === get_object_vars($actual);
        } catch (Throwable) {
            return false;
        }
    }
}
