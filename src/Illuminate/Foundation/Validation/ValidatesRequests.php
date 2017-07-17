<?php

namespace Illuminate\Foundation\Validation;

use Illuminate\Http\Request;
use Illuminate\Contracts\Validation\Factory;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\PreconditionFailedHttpException;

trait ValidatesRequests
{
    /**
     * Run the validation routine against the given validator.
     *
     * @param  \Illuminate\Contracts\Validation\Validator|array  $validator
     * @param  \Illuminate\Http\Request|null  $request
     * @return array
     */
    public function validateWith($validator, Request $request = null)
    {
        $request = $request ?: request();

        if (is_array($validator)) {
            $validator = $this->getValidationFactory()->make($request->all(), $validator);
        }

        $validator->validate();

        return $request->only(
            array_keys($validator->getRules())
        );
    }

    /**
     * Validate the given request with the given rules.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  array  $rules
     * @param  array  $messages
     * @param  array  $customAttributes
     * @return array
     */
    public function validate(Request $request, array $rules,
                             array $messages = [], array $customAttributes = [])
    {
        $this->getValidationFactory()
             ->make($request->all(), $rules, $messages, $customAttributes)
             ->validate();

        return $request->only(array_keys($rules));
    }

    /**
     * Validate the request preconditions.
     *
     * @param \Illuminate\Http\Request $request
     * @param  string|null  $eTag
     * @param  string|null  $lastModified
     * @return $this
     */
    public function validatePreconditions(Request $request, $eTag = null, $lastModified = null)
    {
        if ($request->passesPreconditions($eTag, $lastModified) === false) {
            throw new PreconditionFailedHttpException;
        }

        return $this;
    }

    /**
     * Validate the request preconditions using an entity.
     *
     * @param \Illuminate\Http\Request $request
     * @param mixed $entity
     * @return $this
     */
    public function validateEntityPreconditions(Request $request, $entity = null)
    {
        if ($entity === null) {
            return $this;
        }

        $etag = method_exists($entity, 'getEtag') ? $entity->getEtag() : null;
        $lastModified = method_exists($entity, 'getLastModified') ? $entity->getEtag() : null;

        return $this->validatePreconditions($request, $etag, $lastModified);
    }

    /**
     * Validate the given request with the given rules.
     *
     * @param  string  $errorBag
     * @param  \Illuminate\Http\Request  $request
     * @param  array  $rules
     * @param  array  $messages
     * @param  array  $customAttributes
     * @return array
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function validateWithBag($errorBag, Request $request, array $rules,
                                    array $messages = [], array $customAttributes = [])
    {
        try {
            return $this->validate($request, $rules, $messages, $customAttributes);
        } catch (ValidationException $e) {
            $e->errorBag = $errorBag;

            throw $e;
        }
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
}
