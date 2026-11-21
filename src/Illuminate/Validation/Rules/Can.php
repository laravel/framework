<?php

namespace Illuminate\Validation\Rules;

use Closure;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Contracts\Validation\ValidatorAwareRule;
use Illuminate\Support\Facades\Gate;

class Can implements Rule, ValidatorAwareRule
{
    /**
     * The ability to check.
     *
     * @var string
     */
    protected $ability;

    /**
     * The arguments to pass to the authorization check.
     *
     * @var array
     */
    protected $arguments;

    /**
     * The callback used to resolve the authorization arguments from the value.
     *
     * @var (\Closure(mixed, string): array<mixed>)|null
     */
    protected ?Closure $using = null;

    /**
     * The denial reason from the last failed check.
     */
    protected string $reason = '';

    /**
     * The current validator instance.
     *
     * @var \Illuminate\Validation\Validator
     */
    protected $validator;

    /**
     * Constructor.
     *
     * @param  string  $ability
     * @param  array  $arguments
     */
    public function __construct($ability, array $arguments = [])
    {
        $this->ability = $ability;
        $this->arguments = $arguments;
    }

    /**
     * Resolve the authorization arguments from the value using the given callback.
     *
     * The returned array is passed to the gate as-is, in place of the constructor arguments.
     *
     * @param  \Closure(mixed, string): array<mixed>  $callback
     * @return $this
     */
    public function using(Closure $callback): static
    {
        $this->using = $callback;

        return $this;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        $response = Gate::inspect($this->ability, $this->using
            ? call_user_func($this->using, $value, $attribute)
            : array_values(array_filter([...$this->arguments, $value], fn ($argument) => ! is_null($argument)))
        );

        $this->reason = $response->denied() ? (string) $response->message() : '';

        return $response->allowed();
    }

    /**
     * Get the validation error message.
     *
     * @return array
     */
    public function message()
    {
        $message = $this->validator->getTranslator()->get('validation.can');

        return $message === 'validation.can'
            ? ['The :attribute field contains an unauthorized value. :reason']
            : $message;
    }

    /**
     * Set the current validator.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return $this
     */
    public function setValidator($validator)
    {
        $this->validator = $validator;

        $validator->addReplacer(static::class, fn ($message) => trim(str_replace(':reason', $this->reason, $message)));

        return $this;
    }
}
