<?php

namespace Illuminate\Contracts\Validation;

interface DataAwareRule
{
    /**
     * Set the data under validation.
     *
     * @return $this
     */
    public function setData(array $data);
}
