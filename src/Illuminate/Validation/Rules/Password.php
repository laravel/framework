<?php

namespace Illuminate\Validation\Rules;

use Illuminate\Container\Container;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Contracts\Validation\UncompromisedVerifier;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

class Password implements Rule, DataAwareRule
{
    /**
     * The data under validation.
     *
     * @var array
     */
    protected $data;

    /**
     * The minimum size of the password.
     *
     * @var int
     */
    protected $min = 8;

    /**
     * If the password requires at least one uppercase and one lowercase letter.
     *
     * @var bool
     */
    protected $mixedCase = false;

    /**
     * If the password requires at least one letter.
     *
     * @var bool
     */
    protected $letters = false;

    /**
     * If the password requires at least one number.
     *
     * @var bool
     */
    protected $numbers = false;

    /**
     * If the password requires at least one symbol.
     *
     * @var bool
     */
    protected $symbols = false;

    /**
     * If the password should has not been compromised in data leaks.
     *
     * @var bool
     */
    protected $uncompromised = false;

    /**
     * The failure messages, if any.
     *
     * @var array
     */
    protected $messages = [];

    /**
     * Create a new rule instance.
     *
     * @param  int  $min
     * @return void
     */
    public function __construct($min)
    {
        $this->min = max((int) $min, 1);
    }

    /**
     * Set the data under validation.
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
     * Sets the minimum size of the password.
     *
     * @param  int $size
     * @return $this
     */
    public static function min($size)
    {
        return new static($size);
    }

    /**
     * Ensures the password has not been compromised in data leaks.
     *
     * @return $this
     */
    public function uncompromised()
    {
        $this->uncompromised = true;

        return $this;
    }

    /**
     * Makes the password require at least one uppercase and one lowercase letter.
     *
     * @return $this
     */
    public function mixedCase()
    {
        $this->mixedCase = true;

        return $this;
    }

    /**
     * Makes the password require at least one letter.
     *
     * @return $this
     */
    public function letters()
    {
        $this->letters = true;

        return $this;
    }

    /**
     * Makes the password require at least one number.
     *
     * @return $this
     */
    public function numbers()
    {
        $this->numbers = true;

        return $this;
    }

    /**
     * Makes the password require at least one symbol.
     *
     * @return $this
     */
    public function symbols()
    {
        $this->symbols = true;

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
        $validator = Validator::make($this->data, [
            $attribute => 'string|min:'.$this->min,
        ]);

        if ($validator->fails()) {
            return $this->fail($validator->messages()->all());
        }

        $value = (string) $value;

        if ($this->mixedCase && ! preg_match('/(\p{Ll}+.*\p{Lu})|(\p{Lu}+.*\p{Ll})/u', $value)) {
            $this->fail('The :attribute must contain at least one uppercase and one lowercase letter.');
        }

        if ($this->letters && ! preg_match('/\pL/', $value)) {
            $this->fail('The :attribute must contain at least one letter.');
        }

        if ($this->symbols && ! preg_match('/\p{Z}|\p{S}|\p{P}/', $value)) {
            $this->fail('The :attribute must contain at least one symbol.');
        }

        if ($this->numbers && ! preg_match('/\pN/', $value)) {
            $this->fail('The :attribute must contain at least one number.');
        }

        if (! empty($this->messages)) {
            return false;
        }

        if ($this->uncompromised && ! Container::getInstance()->make(UncompromisedVerifier::class)->verify($value)) {
            return $this->fail(
                'The given :attribute has appeared in a data leak. Please choose a different :attribute.'
            );
        }

        return true;
    }

    /**
     * Get the validation error message.
     *
     * @return array
     */
    public function message()
    {
        return $this->messages;
    }

    /**
     * Adds the given failures, and return false.
     *
     * @param  array|string $message
     * @return bool
     */
    protected function fail($messages)
    {
        $messages = collect(Arr::wrap($messages))->map(function ($message) {
            return __($message);
        })->all();

        $this->messages = array_merge($this->messages, $messages);

        return false;
    }
}
