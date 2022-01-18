<?php

namespace Illuminate\Validation;

use Illuminate\Support\Arr;

class NestedRules
{
    /**
     * The callback to execute.
     *
     * @var callable
     */
    protected $callback;

    /**
     * Create a new nested rule instance.
     *
     * @param  callable  $callback
     * @return void
     */
    public function __construct(callable $callback)
    {
        $this->callback = $callback;
    }

    /**
     * Compile the callback into an array of rules.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  mixed  $data
     * @return \stdClass
     */
    public function compile($attribute, $value, $data = null)
    {
        $rules = call_user_func($this->callback, $attribute, $value, $data);

        $parser = new ValidationRuleParser(
            Arr::undot(Arr::wrap($data))
        );

        return is_array($rules) && Arr::isAssoc($rules)
            ? $parser->explode(Arr::dot($rules, "$attribute."))
            : $parser->explode([$attribute => $rules]);
    }
}
