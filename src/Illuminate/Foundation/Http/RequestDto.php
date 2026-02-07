<?php

namespace Illuminate\Foundation\Http;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\Access\Response;
use Illuminate\Container\Container;
use Illuminate\Contracts\Container\SelfBuilding;
use Illuminate\Contracts\Validation\Factory as ValidationFactory;
use Illuminate\Foundation\Precognition;
use Illuminate\Http\Request;
use Illuminate\Validation\Validator;

class RequestDto implements SelfBuilding
{
    public static function newInstance()
    {
        /** @var Request $request */
        $request = Container::getInstance()->make('request');
        static::prepareForValidation($request);

        if (! static::passesAuthorization($request)) {
            static::failedAuthorization($request);
        }

        $instance = static::getValidatorInstance($request);

        if ($request->isPrecognitive()) {
            $instance->after(Precognition::afterValidationHook($request));
        }

        if ($instance->fails()) {
            static::failedValidation($instance);
        }

        static::passedValidation();

        // @todo validated only works on data which has been validated... they SHOULD always validate the data but it's possible they do not
        return static::buildDto($instance->validated());
    }

    protected static function buildDto(array $validated)
    {
        // @todo add ability to map request to DTO params
        // @todo handle nested objects
        return new static(...$validated);
    }

    /**
     * Handle a passed validation attempt.
     *
     * @return void
     */
    protected static function passedValidation()
    {
        //
    }

    /**
     * Handle a failed validation attempt.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    protected static function failedValidation(Validator $validator)
    {
        $exception = $validator->getException();

        throw new $exception($validator);
    }

    protected static function prepareForValidation(Request $request)
    {
        //
    }

    /**
     * Get the validator instance for the request.
     *
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected static function getValidatorInstance(Request $request)
    {
        $factory = Container::getInstance()->make(ValidationFactory::class);

        if (method_exists(static::class, 'validator')) {
            $validator = Container::getInstance()->call(static::validator(...), ['factory' => $factory]);
        } else {
            $validator = static::createDefaultValidator($factory, $request);
        }

        if (method_exists(static::class, 'after')) {
            $validator->after(Container::getInstance()->call(
                self::after(...),
                ['validator' => $validator]
            ));
        }

        return $validator;
    }

    protected static function validationRules()
    {
        // @todo add in attribute check
        return method_exists(static::class, 'rules')
            ? Container::getInstance()->call(static::rules(...))
            : [];
    }

    /**
     * Create the default validator instance.
     *
     * @param  \Illuminate\Contracts\Validation\Factory  $factory
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected static function createDefaultValidator(ValidationFactory $factory, Request $request)
    {
        $validator = $factory->make(
            static::validationData(),
            static::validationRules(),
            static::messages(),
            static::attributes(),
        )->stopOnFirstFailure(static::shouldStopOnFirstFailure());

        if ($request->isPrecognitive()) {
            $validator->setRules(
                $request->filterPrecognitiveRules($validator->getRulesWithoutPlaceholders())
            );
        }

        return $validator;
    }

    protected static function shouldStopOnFirstFailure(): bool
    {
        // @todo use Taylor's attribute to check
        return false;
    }

    protected static function messages()
    {
        return [];
    }

    protected static function attributes()
    {
        return [];
    }

    protected static function getRequest(): Request
    {
        return Container::getInstance()->make('request');
    }

    protected static function validationData(): array
    {
        return static::getRequest()->all();
    }

    protected static function passesAuthorization(Request $request)
    {
        if (method_exists(static::class, 'authorize')) {
            $result = Container::getInstance()->call([static::class, 'authorize'], ['request' => $request]);

            return $result instanceof Response ? $result->authorize() : $result;
        }

        return true;
    }
    protected static function failedAuthorization(Request $request)
    {
        throw new AuthorizationException;
    }
}
