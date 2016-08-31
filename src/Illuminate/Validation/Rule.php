<?php

namespace Illuminate\Validation;

use Illuminate\Contracts\Validation\Rule as RuleContract;

abstract class Rule implements RuleContract
{
    /**
     * Defines if rule is implicit.
     *
     * @var bool
     */
    protected $implicit = false;

    /**
     * Defines required parameters count for validator.
     *
     * @var int
     */
    protected $requiredParametersCount = 0;

    /**
     * Wheter to allow parsing named parameters.
     *
     * @var bool
     */
    protected $allowNamedParameters = false;

    /**
     * Maps associative parameters to.
     *
     * @param  array  $parameters
     * @return array
     */
    public function mapParameters($parameters)
    {
        return $parameters;
    }

    /**
     * Determines if rule is implicit based on implicit field.
     *
     * @return bool
     */
    public function isImplicit()
    {
        return $this->implicit;
    }

    /**
     * Returns required parameters count.
     *
     * @return int
     */
    public function getRequiredParametersCount()
    {
        return $this->requiredParametersCount;
    }

    /**
     * Returns wheter named parameters are allowed.
     *
     * @return bool
     */
    public function allowNamedParameters()
    {
        return $this->allowNamedParameters;
    }

    /**
     * Returns whether rule is valid or not.
     *
     * @param  string $attribute
     * @param  mixed $value
     * @param  array $parameters
     * @param  \Illuminate\Validation\Validator $validator
     * @return bool
     */
    abstract public function passes($attribute, $value, $parameters, $validator);
}
