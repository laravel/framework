<?php

namespace Illuminate\Support\Testing\Fakes;

use Illuminate\Auth\Access\DefinesAbilities;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Support\Traits\ForwardsCalls;
use ReflectionClass;

class PolicyFake implements DefinesAbilities
{
    use ForwardsCalls;

    /**
     * @var mixed
     */
    private $originalPolicy;

    /**
     * @var array
     */
    private $abilitiesToIntercept = [];

    /**
     * @var bool
     */
    private $checkOriginalAbility = false;

    /**
     * @var array
     */
    private $trackedAbilities = [];

    public function __construct($originalPolicy)
    {
        $this->originalPolicy = $originalPolicy;
    }

    /**
     * @param  bool $checkOriginalAbility
     * @return $this
     */
    public function checkOriginalAbility($checkOriginalAbility = true)
    {
        $this->checkOriginalAbility = $checkOriginalAbility;

        return $this;
    }

    /**
     * @param  string $ability
     * @param  \Illuminate\Auth\Access\Response|Sequence|null $value
     * @return $this
     */
    public function fail(string $ability, $value = null)
    {
        $this->abilitiesToIntercept[$ability] = $value ?? false;

        return $this;
    }

    public function checked($ability, $callback = null)
    {
        if (! $this->hasChecked($ability)) {
            return collect();
        }

        $callback ??= fn () => true;

        return collect($this->trackedAbilities[$ability])->filter(fn ($arguments) => $callback(...$arguments));
    }

    /**
     * Returns the policy that is being decorated.
     *
     * @return mixed
     */
    public function getOriginalPolicy()
    {
        return $this->originalPolicy;
    }

    /**
     * @param  string $ability
     * @param  array $arguments
     * @return mixed
     */
    public function __call($ability, $arguments)
    {
        $this->trackedAbilities[$ability][] = $arguments;

        if (in_array($ability, array_keys($this->abilitiesToIntercept))) {
            if ($this->checkOriginalAbility) {
                $this->forwardCallTo($this->originalPolicy, $ability, $arguments);
            }

            $response = $this->abilitiesToIntercept[$ability];

            if ($response instanceof Sequence) {
                if ($response->count() === 0) {
                    return false;
                }

                return $response();
            }

            return $response;
        }

        return $this->forwardDecoratedCallTo($this->originalPolicy, $ability, $arguments);
    }

    public function abilities()
    {
        return collect((new ReflectionClass($this->originalPolicy))->getMethods())
            ->keyBy->getName()
            ->all();
    }

    /**
     * @param  string $ability
     * @return bool
     */
    private function hasChecked($ability)
    {
        return isset($this->trackedAbilities[$ability]);
    }
}
