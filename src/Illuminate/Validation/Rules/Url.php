<?php

namespace Illuminate\Validation\Rules;

use Illuminate\Support\Arr;
use Illuminate\Support\Traits\Conditionable;
use Stringable;

class Url implements Stringable
{
    use Conditionable;

    /**
     * The constraints for the URL rule.
     */
    protected array $constraints = [];

    /**
     * The allowed URL schemes.
     */
    protected array $schemes = [];

    /**
     * The field under validation must be an active URL with a valid DNS record.
     *
     * @return $this
     */
    public function active(): static
    {
        return $this->addRule('active_url');
    }

    /**
     * The field under validation must be a URL with an https scheme.
     *
     * @return $this
     */
    public function httpsOnly(): static
    {
        $this->schemes = ['https'];

        return $this;
    }

    /**
     * The field under validation must be a URL with one of the given schemes.
     *
     * @param  array|string  ...$schemes
     * @return $this
     */
    public function schemes(array|string ...$schemes): static
    {
        $this->schemes = Arr::flatten($schemes);

        return $this;
    }

    /**
     * Convert the rule to a validation string.
     */
    public function __toString(): string
    {
        $url = empty($this->schemes) ? 'url' : 'url:'.implode(',', $this->schemes);

        return empty($this->constraints)
            ? $url
            : $url.'|'.implode('|', array_unique($this->constraints));
    }

    /**
     * Add custom rules to the validation rules array.
     */
    protected function addRule(array|string $rules): static
    {
        $this->constraints = array_merge($this->constraints, Arr::wrap($rules));

        return $this;
    }
}
