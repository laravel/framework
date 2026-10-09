<?php

namespace Illuminate\Validation\Rules;

use Illuminate\Contracts\Validation\ParameterizedRule;
use Illuminate\Validation\Rules\Concerns\FormatsParameters;

class Url implements ParameterizedRule
{
    use FormatsParameters;

    /**
     * @var string[]|null
     */
    protected ?array $protocols = null;

    /**
     * Specify the allowed URL protocols.
     *
     * @param  string[]|null  $protocols
     */
    public function protocols(array $protocols): static
    {
        $this->protocols = $protocols;

        return $this;
    }

    /**
     * Get the rule name followed by its parameters.
     */
    public function toArray(): array
    {
        return ['url', ...array_values($this->protocols ?? [])];
    }
}
