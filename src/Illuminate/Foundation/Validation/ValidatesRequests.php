<?php

namespace Illuminate\Foundation\Validation;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\UrlGenerator;
use Illuminate\Contracts\Validation\Factory;
use Illuminate\Contracts\Validation\Validator;

trait ValidatesRequests
{
    /**
     * Conditional validation rules based on a Closure.
     *
     * @var array
     */
    protected $validatesRequestSometimes = [];

    /**
     * The default error bag.
     *
     * @var string
     */
    protected $validatesRequestErrorBag;

    /**
     * Add conditions to a given field based on a Closure.
     *
     * @param  string|array  $attribute
     * @param  string|array  $rules
     * @param  callable  $callback
     * @return object
     */
    public function sometimes($attribute, $rules, callable $callback)
    {
        array_push($this->validatesRequestSometimes, compact('attribute', 'rules', 'callback'));

        return $this;
    }

    /**
     * Validate the given request with the given rules.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  array  $rules
     * @param  array  $messages
     * @param  array  $customAttributes
     * @return void
     */
    public function validate(Request $request, array $rules, array $messages = [], array $customAttributes = [])
    {
        $validator = $this->makeValidator($request->all(), $rules, $messages, $customAttributes);

        if ($validator->fails()) {
            $this->throwValidationException($request, $validator);
        }
    }

    /**
     * Validate the given request with the given rules.
     *
     * @param  string  $errorBag
     * @param  \Illuminate\Http\Request  $request
     * @param  array  $rules
     * @param  array  $messages
     * @param  array  $customAttributes
     * @return void
     *
     * @throws \Illuminate\Foundation\Validation\ValidationException
     */
    public function validateWithBag($errorBag, Request $request, array $rules, array $messages = [], array $customAttributes = [])
    {
        $this->withErrorBag($errorBag, function () use ($request, $rules, $messages, $customAttributes) {
            $this->validate($request, $rules, $messages, $customAttributes);
        });
    }

    /**
     * Throw the failed validation exception.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     * @return void
     *
     * @throws \Illuminate\Foundation\Validation\ValidationException
     */
    protected function throwValidationException(Request $request, $validator)
    {
        throw new ValidationException($validator, $this->buildFailedValidationResponse(
            $request, $this->formatValidationErrors($validator)
        ));
    }

    /**
     * Create the response for when a request fails validation.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  array  $errors
     * @return \Illuminate\Http\Response
     */
    protected function buildFailedValidationResponse(Request $request, array $errors)
    {
        if (($request->ajax() && ! $request->pjax()) || $request->wantsJson()) {
            return new JsonResponse($errors, 422);
        }

        return redirect()->to($this->getRedirectUrl())
                        ->withInput($request->input())
                        ->withErrors($errors, $this->errorBag());
    }

    /**
     * Format the validation errors to be returned.
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     * @return array
     */
    protected function formatValidationErrors(Validator $validator)
    {
        return $validator->errors()->getMessages();
    }

    /**
     * Get the URL we should redirect to.
     *
     * @return string
     */
    protected function getRedirectUrl()
    {
        return app(UrlGenerator::class)->previous();
    }

    /**
     * Create a new Validator instance.
     *
     * @param  array  $data
     * @param  array  $rules
     * @param  array  $messages
     * @param  array  $customAttributes
     * @return \Illuminate\Validation\Validator
     */
    protected function makeValidator(array $data, array $rules, array $messages = [], array $customAttributes = [])
    {
        $validator = $this->getValidationFactory()->make($data, $rules, $messages, $customAttributes);

        foreach ($this->validatesRequestSometimes as $sometimes) {
            $validator->sometimes($sometimes['attribute'], $sometimes['rules'], $sometimes['callback']);
        }

        $this->validatesRequestSometimes = [];

        return $validator;
    }

    /**
     * Get a validation factory instance.
     *
     * @return \Illuminate\Contracts\Validation\Factory
     */
    protected function getValidationFactory()
    {
        return app(Factory::class);
    }

    /**
     * Execute a Closure within with a given error bag set as the default bag.
     *
     * @param  string  $errorBag
     * @param  callable  $callback
     * @return void
     */
    protected function withErrorBag($errorBag, callable $callback)
    {
        $this->validatesRequestErrorBag = $errorBag;

        call_user_func($callback);

        $this->validatesRequestErrorBag = null;
    }

    /**
     * Get the key to be used for the view error bag.
     *
     * @return string
     */
    protected function errorBag()
    {
        return $this->validatesRequestErrorBag ?: 'default';
    }
}
