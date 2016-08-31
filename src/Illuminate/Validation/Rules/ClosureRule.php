<?php

namespace Illuminate\Validation\Rules;

use Illuminate\Validation\Rule;

class ClosureRule extends Rule
{
    /**
     * @var callable
     */
    protected $closure;

    /**
     * @var bool
     */
    protected $implicit = false;

    /**
     * @param callable $fn
     * @return ClosureRule
     */
    public function setClosure(callable $fn)
    {
        $this->closure = $fn;

        return $this;
    }

    /**
     * @return callable
     */
    public function getClosure()
    {
        return $this->closure;
    }

    /**
     * @param bool $implicit
     * @return ClosureRule
     */
    public function setImplicit($implicit)
    {
        $this->implicit = $implicit;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isImplicit()
    {
        return (bool) $this->implicit;
    }

    public static function mapParameters($parameters)
    {
        return $parameters;
    }

    public function passes($attribute, $value, $parameters, $validator)
    {
        return call_user_func($this->closure, $attribute, $value, $parameters, $validator);
    }
}
