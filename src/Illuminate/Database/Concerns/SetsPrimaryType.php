<?php

namespace Illuminate\Database\Concerns;

trait SetsPrimaryType
{
    /**
     * Returns list of allowed values for --primary option.
     *
     * @return string[]
     */
    protected function allowedPrimaryTypes()
    {
        return ['default', 'uuid', 'ulid'];
    }

    /**
     * Defines default primary key type.
     *
     * @return string
     */
    protected function defaultPrimaryType()
    {
        return 'default';
    }

    /**
     * Evaluates the primary key type to return. If invalid or null, it returns defaultPrimaryType().
     *
     * @return string
     */
    protected function evaluatePrimaryType($primaryType = null)
    {
        return in_array($primaryType, $this->allowedPrimaryTypes()) ? $primaryType : $this->defaultPrimaryType();
    }
}
