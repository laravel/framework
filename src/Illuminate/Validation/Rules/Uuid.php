<?php

namespace Illuminate\Validation\Rules;

use Illuminate\Contracts\Validation\ParameterizedRule;
use Illuminate\Validation\Rules\Concerns\FormatsParameters;

class Uuid implements ParameterizedRule
{
    use FormatsParameters;

    /**
     * @var int<0, 8>|'nil'|'max'|null
     */
    protected int|string|null $version = null;

    /**
     * Specify the UUID version.
     *
     * @param  int<0, 8>|'nil'|'max'  $version
     */
    public function version(int|string $version): static
    {
        $this->version = $version;

        return $this;
    }

    /**
     * Get the rule name followed by its parameters.
     */
    public function toArray(): array
    {
        return $this->version === null ? ['uuid'] : ['uuid', $this->version];
    }
}
