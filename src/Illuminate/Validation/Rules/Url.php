<?php

namespace Illuminate\Validation\Rules;

use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\ValidatorAwareRule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Traits\Conditionable;

class Url implements DataAwareRule, ValidationRule, ValidatorAwareRule
{
    use Conditionable;

    /**
     * The protocols that are allowed.
     *
     * @var array
     */
    protected $protocols = [];

    /**
     * Indicates if the URL must be an "active" URL with a valid DNS record.
     *
     * @var bool
     */
    protected $active = false;

    /**
     * The validator performing the validation.
     *
     * @var \Illuminate\Validation\Validator
     */
    protected $validator;

    /**
     * The data under validation.
     *
     * @var array
     */
    protected $data;

    /**
     * Specify the allowed protocols for the URL.
     *
     * @param  array  $protocols
     * @return $this
     */
    public function protocols(array $protocols)
    {
        $this->protocols = $protocols;

        return $this;
    }

    /**
     * Ensure the URL has a valid DNS record.
     *
     * @return $this
     */
    public function active()
    {
        $this->active = true;

        return $this;
    }

    /**
     * Run the validation rule.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     * @return void
     */
    public function validate(string $attribute, mixed $value, \Closure $fail): void
    {
        $rules = $this->active ? ['active_url'] : [];

        $rules[] = 'url'.($this->protocols ? ':'.implode(',', $this->protocols) : '');

        $validator = Validator::make(
            $this->data,
            [$attribute => $rules],
            $this->validator->customMessages,
            $this->validator->customAttributes
        );

        if ($validator->fails()) {
            foreach ($validator->messages()->all() as $message) {
                $fail($message);
            }
        }
    }

    /**
     * Set the current validator.
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     * @return $this
     */
    public function setValidator($validator)
    {
        $this->validator = $validator;

        return $this;
    }

    /**
     * Set the current data under validation.
     *
     * @param  array  $data
     * @return $this
     */
    public function setData($data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Convert the rule to a string representation.
     *
     * @return string
     */
    public function __toString()
    {
        $rules = $this->active ? ['active_url'] : [];

        $rules[] = 'url'.($this->protocols ? ':'.implode(',', $this->protocols) : '');

        return implode('|', $rules);
    }
}
