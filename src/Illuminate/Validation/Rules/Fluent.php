<?php

namespace Illuminate\Validation\Rules;

use Illuminate\Support\Arr;
use Illuminate\Support\Traits\Conditionable;

class Fluent
{
    use Conditionable;

    /**
     * The constraints for the rules.
     */
    protected array $constraints = [];

    /**
     * The field under validation must be present in the input data and not empty.
     *
     * @return $this
     */
    public function required(): Fluent
    {
        return $this->addRule('required');
    }

    /**
     * The field under validation will be validated only of it present.
     *
     * @return $this
     */
    public function sometimes(): Fluent
    {
        return $this->addRule('sometimes');
    }

    /**
     * The field under validation may be null.
     *
     * @return $this
     */
    public function nullable(): Fluent
    {
        return $this->addRule('nullable');
    }

    /**
     * Stop running validation rules for the field after the first validation failure.
     *
     * @return $this
     */
    public function bail(): Fluent
    {
        return $this->addRule('bail');
    }

    /**
     * The field under validation must be be valid for this validation rule.
     *
     * @param  $validationRule
     * @return $this
     */
    public function shouldBe($validationRule): Fluent
    {
        return $this->addRule($validationRule);
    }

    /**
     * Add custom rules to the validation rules array.
     */
    protected function addRule($rule): Fluent
    {
        if ($rule instanceof Fluent) {
            $rule = $rule->toArray();
        }

        $this->constraints = array_merge($this->constraints, Arr::wrap($rule));

        return $this;
    }

    /**
     * Convert the rule to a validation array.
     * @return array
     */
    public function toArray()
    {
        return $this->constraints;
    }
}
