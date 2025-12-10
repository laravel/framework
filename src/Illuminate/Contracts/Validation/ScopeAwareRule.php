<?php

namespace Illuminate\Contracts\Validation;

interface ScopeAwareRule
{
    /**
     * Set the scoped data (the current array item being validated).
     *
     * @param  array  $scope
     * @return $this
     */
    public function setScope(array $scope);
}
