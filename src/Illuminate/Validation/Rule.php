<?php

namespace Illuminate\Validation;

use Illuminate\Contracts\Validation\Rule as RuleContract;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;

abstract class Rule implements RuleContract
{
    /**
     * Defines if rule is implicit
     *
     * @var boolean
     */
    protected $implicit = false;

    /**
     * Defines required parameters count for validator
     *
     * @var integer
     */
    protected $requiredParametersCount = 0;

    /**
     * Wheter to allow parsing named parameters
     *
     * @var boolean
     */
    protected $allowNamedParameters = false;

    /**
     * Maps associative parameters to
     *
     * @param  array  $parameters
     * @return array
     */
    public function mapParameters($parameters)
    {
        return $parameters;
    }

    /**
     * Determines if rule is implicit based on implicit field
     *
     * @return boolean
     */
    public function isImplicit()
    {
        return $this->implicit;
    }

    /**
     * Returns required parameters count
     *
     * @return integer
     */
    public function getRequiredParametersCount()
    {
        return $this->requiredParametersCount;
    }

    /**
     * Returns wheter named parameters are allowed
     *
     * @return boolean
     */
    public function allowNamedParameters()
    {
        return $this->allowNamedParameters;
    }

    /**
     * Returns whether rule is valid or not
     *
     * @param  string $attribute
     * @param  mixed $value
     * @param  array $parameters
     * @param  \Illuminate\Validation\Validator $validator
     * @return boolean
     */
    public abstract function passes($attribute, $value, $parameters, $validator);
}
