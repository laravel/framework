<?php

namespace Illuminate\Database\Eloquent\Casts\Concerns;

use InvalidArgumentException;

trait NormalizesArguments
{
    /**
     * Normalize the arguments received by the Cast Attributes.
     *
     * @return void
     */
    protected function normalize(): void
    {
        if (! $this->using) {
            $this->using = $this->class;
        } elseif (! is_a($this->using, $this->class, true)) {
            throw new InvalidArgumentException("The provided class must extend [$this->class].");
        }

        $this->withoutObjectCaching = filter_var($this->withoutObjectCaching, FILTER_VALIDATE_BOOL);

        $this->encrypt = filter_var($this->encrypt, FILTER_VALIDATE_BOOL);
    }
}
