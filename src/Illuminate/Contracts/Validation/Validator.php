<?php

namespace Illuminate\Contracts\Validation;

use Illuminate\Contracts\Support\MessageProvider;

interface Validator extends MessageProvider
{
    /**
     * Run the validator's rules against its data.
     *
     * @return array
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function validate();

    /**
     * Get the attributes and values that were validated.
     *
     * @return array
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function validated();

    /**
     * Set the casts to apply to validated data.
     *
     * @param  array<string, string|\Illuminate\Contracts\Support\CastsValue|\Illuminate\Contracts\Database\Eloquent\CastsAttributes|class-string>  $casts
     * @return $this
     */
    public function casts(array $casts);

    /**
     * Validate the data and return casted results.
     *
     * @return array<string, mixed>
     *
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Illuminate\Validation\InvalidCastException
     */
    public function validateAndCast();

    /**
     * Get the casted validated data.
     *
     * @param  string|null  $key
     * @param  mixed  $default
     * @return mixed
     *
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Illuminate\Validation\InvalidCastException
     */
    public function casted($key = null, $default = null);

    /**
     * Determine if the data fails the validation rules.
     *
     * @return bool
     */
    public function fails();

    /**
     * Get the failed validation rules.
     *
     * @return array
     */
    public function failed();

    /**
     * Add conditions to a given field based on a Closure.
     *
     * @param  string|array  $attribute
     * @param  string|array  $rules
     * @param  callable  $callback
     * @return $this
     */
    public function sometimes($attribute, $rules, callable $callback);

    /**
     * Add an after validation callback.
     *
     * @param  callable|string  $callback
     * @return $this
     */
    public function after($callback);

    /**
     * Get all of the validation error messages.
     *
     * @return \Illuminate\Support\MessageBag
     */
    public function errors();
}
