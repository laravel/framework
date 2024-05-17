<?php

namespace Illuminate\Validation\Rules;

use InvalidArgumentException;
use Stringable;
use Exception;

class RequiredIfMethod implements Stringable
{
    /**
     * The HTTP method that triggers the required condition.
     *
     * @var string
     */
    protected $method;

    /**
     * Create a new required validation rule based on the request method.
     *
     * @param  string  $method
     * @return void
     */
    public function __construct($method)
    {
        $validMethods = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'];
        $method = strtoupper($method);

        if (!in_array($method, $validMethods)) {
            throw new InvalidArgumentException("Invalid HTTP method: $method");
        }

        $this->method = $method;
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
        return request()->isMethod($this->method) ? !empty($value) : true;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return "The :attribute field is required when the request method is {$this->method}.";
    }

    /**
     * Convert the rule to a validation string.
     *
     * @return string
     */
    public function __toString()
    {
        return "required_if_method:{$this->method}";
    }

    public function __sleep()
    {
        throw new Exception("Cannot serialize " . __CLASS__);
    }
}
