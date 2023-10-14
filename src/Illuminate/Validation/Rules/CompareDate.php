<?php

namespace Illuminate\Validation\Rules;

use Illuminate\Support\Str;

class CompareDate
{
    /**
     * The name of the rule.
     *
     * @var string
     */
    protected $rule;

    /**
     * The comparable date.
     *
     * @var int|string|\DateTimeInterface
     */
    protected $date;

    /**
     * Create a new in rule instance.
     *
     * @param  $date
     * @param  string  $rule
     * @return void
     */
    public function __construct($date, string $rule = '==')
    {
        $this->date = $date;

        $this->rule = $rule;
    }

    /**
     * Extend validation rule to also accept input equal to given date.
     *
     * @return $this
     */
    public function orEqual()
    {
        if (! Str::endsWith($this->rule, '=')) {
            $this->rule .= '=';
        }

        return $this;
    }

    /**
     * Convert compare operator to corresponding validation rule.
     *
     * @return string
     */
    protected function validationRule(): string
    {
        return match ($this->rule) {
            '=' => 'date_equals',
            '<' => 'before',
            '<=' => 'before_or_equal',
            '>' => 'after',
            '>=' => 'after_or_equal',
        };
    }

    /**
     * Convert the rule to a validation string.
     *
     * @return string
     *
     * @see \Illuminate\Validation\ValidationRuleParser::parseParameters
     */
    public function __toString()
    {
        return $this->validationRule().':'.$this->date;
    }
}
