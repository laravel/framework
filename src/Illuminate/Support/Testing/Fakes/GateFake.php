<?php

namespace Illuminate\Support\Testing\Fakes;

use Illuminate\Contracts\Auth\Access\Authorizable;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Container\Container;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\ForwardsCalls;
use PHPUnit\Framework\Assert as PHPUnit;

class GateFake implements Gate
{
    use ForwardsCalls;

    /**
     * The container instance.
     *
     * @var \Illuminate\Contracts\Container\Container
     */
    protected $container;

    /**
     * The original Gate implementation.
     *
     * @var \Illuminate\Contracts\Auth\Access\Gate
     */
    protected $gate;

    /**
     * The abilities to intercept instead of checked.
     *
     * @var array
     */
    protected $abilitiesToIntercept = [];

    /**
     * The abilities that have been checked.
     *
     * @var array
     */
    protected $trackedAbilities = [];

    /**
     * The policies that have been checked.
     *
     * @var array
     */
    private $trackedPolicies = [];

    /**
     * Should we execute the original gate/ability.
     *
     * @var bool
     */
    protected $checkOriginal = false;

    /**
     * All the fake policies that have been generated.
     *
     * @var array<class-string, string>
     */
    private $generatedPolicies = [];

    /**
     * All policy methods to not fake and execute as normal.
     *
     * @var array
     */
    private $policiesToIntercept = [];

    public function __construct(Gate $gate, Container $container)
    {
        $this->gate = $gate;
        $this->container = $container;

        $gate->before(function (?Authorizable $user, $ability, $arguments) {
            if (isset($arguments[0]) && $policy = $this->gate->getPolicyFor($arguments[0])) {
                $policyClass = get_class($policy);
                // If it isn't a fake policy then it won't be tracking the usage of the policy,
                // so we will track it in here.
                if (! $policy instanceof PolicyFake) {
                    $this->trackedPolicies[$policyClass][$ability][] = $arguments;
                }

                if ($policy instanceof PolicyFake) {
                    $policyClass = $this->gate->policies()[get_class($arguments[0])];
                    $policyClass = array_flip($this->generatedPolicies)[$policyClass];
                }

                if (isset($this->policiesToIntercept[$policyClass]) && in_array($ability, $this->policiesToIntercept[$policyClass])) {
                    return null;
                }

                return true;
            }

            $this->trackedAbilities[$ability][] = $arguments;

            if (in_array($ability, array_keys($this->abilitiesToIntercept))) {
                return null;
            }

            if ($this->checkOriginal) {
                $callback = $this->getOriginalGate($ability);

                $callback($user, ...$arguments);
            }

            return true;
        });
    }

    /**
     * @return $this
     */
    public function checkOriginalGate()
    {
        $this->checkOriginal = true;

        return $this;
    }

    /**
     * @param  string $policy
     * @param  ?string $ability
     * @return $this
     */
    public function except($policy, $ability = null)
    {
        if ($this->isPolicy($policy)) {
            $this->policiesToIntercept[$policy][] = $ability;

            return $this;
        }

        $this->abilitiesToIntercept[$policy] = true;

        return $this;
    }

    /**
     * @param  string $policy
     * @param  \Illuminate\Auth\Access\Response|Sequence|bool|null $value
     * @return $this
     */
    public function fail($policy, $ability = null, $value = null)
    {
        if ($this->isPolicy($policy)) {
            return $this->failPolicy($policy, $ability, $value);
        }

        $value = $ability;
        $this->abilitiesToIntercept[$policy] = $value ?? false;
        $originalAbility = $this->getOriginalGate($policy);
        $this->gate->define($policy, function ($user = null, ...$arguments) use ($value, $originalAbility, $policy) {
            if ($this->checkOriginal) {
                $originalAbility($user, ...$arguments);
            }

            $response = $this->abilitiesToIntercept[$policy];

            if ($response instanceof Sequence) {
                if ($response->count() === 0) {
                    return false;
                }

                return $response();
            }

            return $response;
        });

        return $this;
    }

    /**
     * @param  class-string $policy
     * @param  string $method
     * @param  \Illuminate\Auth\Access\Response|Sequence|bool|null $value
     * @return $this
     */
    public function failPolicy($policy, $method, $value)
    {
        $policyClass = $this->generatedPolicies[$policy] ?? Str::random() . ':' . $policy;
        $this->generatedPolicies[$policy] = $policyClass;
        $this->policiesToIntercept[$policy][] = $method;

        collect($this->gate->policies())
            ->filter(fn ($key) => $key === $policy)
            ->keys()
            ->each(fn ($model) => $this->gate->policy($model, $policyClass));

        if ($this->container->resolved($policyClass)) {
            $this->container->get($policyClass)
                ->fail($method, $value)
                ->checkOriginalAbility($this->checkOriginal);

            return $this;
        }

        $this->container->instance(
            $policyClass,
            (new PolicyFake($this->container->get($policy)))
                ->fail($method, $value)
                ->checkOriginalAbility($this->checkOriginal)
        );

        return $this;
    }

    /**
     * Assert the given policy ability or gate has been checked.
     *
     * @param  string $policy
     * @param  ?string $ability
     * @param  ?callable $callback
     * @return void
     */
    public function assertChecked($policy, $ability = null, $callback = null)
    {
        if (is_int($ability) || is_int($callback)) {
            return $this->assertCheckedTimes($policy, $ability, $callback);
        }

        PHPUnit::assertTrue($this->checked($policy, $ability, $callback)->isNotEmpty());
    }

    /**
     * Assert the given policy ability or gate is called X amount of times.
     *
     * @param  string $policy The policy class or the gate name.
     * @param  string $ability
     * @param  int $times
     * @return void
     */
    public function assertCheckedTimes($policy, $ability = null, $times = null)
    {
        if (is_int($ability)) {
            $times = $ability;
            $ability = null;
        }

        $count = $this->checked($policy, $ability)->count();

        PHPUnit::assertSame(
            $times, $count,
            "The expected [{$ability}] ability was checked {$count} times instead of {$times} times."
        );
    }

    /**
     * @param  string $policy
     * @param  string $ability
     * @param  ?callable $callback
     * @return \Illuminate\Support\Collection
     *
     */
    private function checked($policy, $ability, $callback = null)
    {
        if ($this->isPolicy($policy)) {
            $policyInstance = $this->getFakedPolicy($policy);
            $callback ??= fn () => true;

            // The developer hasn't overridden the policy with ->fail() so we use our
            // internal cache of tracked policies to check if the ability has been called.
            if ($policyInstance === null) {
                return collect($this->trackedPolicies[$policy][$ability])
                    ->filter(fn ($arguments) => $callback(...$arguments));
            }

            return $policyInstance->checked($ability, $callback);
        }

        $callback = $ability ?? fn () => true;
        $ability = $policy;
        if (! $this->hasChecked($ability)) {
            return collect();
        }

        return collect($this->trackedAbilities[$ability])->filter(fn ($arguments) => $callback(...$arguments));
    }

    /**
     * @param  string $ability
     * @return bool
     */
    private function hasChecked($ability)
    {
        return isset($this->trackedAbilities[$ability]) && ! empty($this->trackedAbilities[$ability]);
    }

    /** @inheritDoc */
    public function has($ability)
    {
        return $this->forwardDecoratedCallTo($this->gate, 'has', [$ability]);
    }

    /** @inheritDoc */
    public function define($ability, $callback)
    {
        return $this->forwardDecoratedCallTo($this->gate, 'define', [$ability, $callback]);
    }

    /** @inheritDoc */
    public function resource($name, $class, array $abilities = null)
    {
        return $this->forwardDecoratedCallTo($this->gate, 'resource', [$name, $class, $abilities]);
    }

    /** @inheritDoc */
    public function policy($class, $policy)
    {
        return $this->forwardDecoratedCallTo($this->gate, 'policy', [$class, $policy]);
    }

    /** @inheritDoc */
    public function before(callable $callback)
    {
        return $this->forwardDecoratedCallTo($this->gate, 'before', [$callback]);
    }

    /** @inheritDoc */
    public function after(callable $callback)
    {
        return $this->forwardDecoratedCallTo($this->gate, 'after', [$callback]);
    }

    /** @inheritDoc */
    public function allows($ability, $arguments = [])
    {
        return $this->forwardDecoratedCallTo($this->gate, 'allows', [$ability, $arguments]);
    }

    /** @inheritDoc */
    public function denies($ability, $arguments = [])
    {
        return $this->forwardDecoratedCallTo($this->gate, 'denies', [$ability, $arguments]);
    }

    /** @inheritDoc */
    public function check($abilities, $arguments = [])
    {
        return $this->forwardDecoratedCallTo($this->gate, 'check', [$abilities, $arguments]);
    }

    /** @inheritDoc */
    public function any($abilities, $arguments = [])
    {
        return $this->forwardDecoratedCallTo($this->gate, 'any', [$abilities, $arguments]);
    }

    /** @inheritDoc */
    public function authorize($ability, $arguments = [])
    {
        return $this->forwardDecoratedCallTo($this->gate, 'authorize', [$ability, $arguments]);
    }

    /** @inheritDoc */
    public function inspect($ability, $arguments = [])
    {
        return $this->forwardDecoratedCallTo($this->gate, 'inspect', [$ability, $arguments]);
    }

    /** @inheritDoc */
    public function raw($ability, $arguments = [])
    {
        return $this->forwardDecoratedCallTo($this->gate, 'raw', [$ability, $arguments]);
    }

    /** @inheritDoc */
    public function getPolicyFor($class)
    {
        $this->forwardDecoratedCallTo($this->gate, 'getPolicyFor', [$class]);
    }

    /** @inheritDoc */
    public function forUser($user)
    {
        return $this->forwardDecoratedCallTo($this->gate, 'forUser', []);
    }

    /** @inheritDoc */
    public function abilities()
    {
        return $this->forwardDecoratedCallTo($this->gate, 'abilities', []);
    }

    /**
     * @param  string $gate
     * @return ?callable
     */
    private function getOriginalGate($gate)
    {
        return Arr::get($this->gate->abilities(), $gate);
    }

    /**
     * @param  string $policy
     * @return bool
     */
    private function isPolicy($policy)
    {
        return class_exists($policy) || $this->container->has($policy);
    }

    /**
     * Return the PolicyFake for the given policy if it exists.
     *
     * @param  string $policy
     * @return \Illuminate\Support\Testing\Fakes\PolicyFake|string|null
     */
    private function getFakedPolicy(string $policy)
    {
        $policyClass = $this->generatedPolicies[$policy] ?? null;

        if ($policyClass === null) {
            return null;
        }

        return $this->container->get($policyClass);
    }
}
