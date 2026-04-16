<?php

namespace Illuminate\Foundation\Http;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Validation\Factory as ValidationFactory;
use Illuminate\Contracts\Validation\ValidatesWhenResolved;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\Attributes\ErrorBag;
use Illuminate\Foundation\Http\Attributes\FailOnUnknownFields;
use Illuminate\Foundation\Http\Attributes\RedirectTo;
use Illuminate\Foundation\Http\Attributes\RedirectToRoute;
use Illuminate\Foundation\Http\Attributes\StopOnFirstFailure;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidatesWhenResolvedTrait;
use ReflectionClass;

class FormRequest extends Request implements ValidatesWhenResolved
{
    use ValidatesWhenResolvedTrait;

    /**
     * The container instance.
     *
     * @var \Illuminate\Contracts\Container\Container
     */
    protected $container;

    /**
     * The redirector instance.
     *
     * @var \Illuminate\Routing\Redirector
     */
    protected $redirector;

    /**
     * The URI to redirect to if validation fails.
     *
     * @var string
     */
    protected $redirect;

    /**
     * The route to redirect to if validation fails.
     *
     * @var string
     */
    protected $redirectRoute;

    /**
     * The controller action to redirect to if validation fails.
     *
     * @var string
     */
    protected $redirectAction;

    /**
     * The key to be used for the view error bag.
     *
     * @var string
     */
    protected $errorBag = 'default';

    /**
     * Indicates whether validation should stop after the first rule failure.
     *
     * @var bool
     */
    protected $stopOnFirstFailure = false;

    /**
     * The validator instance.
     *
     * @var \Illuminate\Contracts\Validation\Validator
     */
    protected $validator;

    /**
     * Indicates if unknown fields should be rejected for all form requests.
     *
     * @var bool
     */
    protected static bool $globalFailOnUnknownFields = false;

    /**
     * Get the validator instance for the request.
     *
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function getValidatorInstance()
    {
        if ($this->validator) {
            return $this->validator;
        }

        $this->configureFromAttributes();

        $factory = $this->container->make(ValidationFactory::class);

        if (method_exists($this, 'validator')) {
            $validator = $this->container->call($this->validator(...), compact('factory'));
        } else {
            $validator = $this->createDefaultValidator($factory);
        }

        if (method_exists($this, 'withValidator')) {
            $this->withValidator($validator);
        }

        if (method_exists($this, 'after')) {
            $validator->after($this->container->call(
                $this->after(...),
                ['validator' => $validator]
            ));
        }

        if ($this->shouldFailOnUnknownFields()) {
            $validator->after(function (Validator $validator) {
                $this->validateNoUnknownFields($validator);
            });
        }

        $this->setValidator($validator);

        return $this->validator;
    }

    /**
     * Configure the form request from class attributes.
     *
     * @return void
     */
    protected function configureFromAttributes()
    {
        $reflection = new ReflectionClass($this);

        if ($reflection->getAttributes(StopOnFirstFailure::class) !== []) {
            $this->stopOnFirstFailure = true;
        }

        $redirectTo = $reflection->getAttributes(RedirectTo::class);

        if ($redirectTo !== []) {
            $this->redirect = $redirectTo[0]->newInstance()->url;
        }

        $redirectToRoute = $reflection->getAttributes(RedirectToRoute::class);

        if ($redirectToRoute !== []) {
            $this->redirectRoute = $redirectToRoute[0]->newInstance()->route;
        }

        $errorBag = $reflection->getAttributes(ErrorBag::class);

        if ($errorBag !== []) {
            $this->errorBag = $errorBag[0]->newInstance()->name;
        }
    }

    /**
     * Create the default validator instance.
     *
     * @param  \Illuminate\Contracts\Validation\Factory  $factory
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function createDefaultValidator(ValidationFactory $factory)
    {
        $rules = $this->validationRules();

        $validator = $factory->make(
            $this->validationData(),
            $rules,
            $this->messages(),
            $this->attributes(),
        )->stopOnFirstFailure($this->stopOnFirstFailure);

        if ($this->isPrecognitive()) {
            $validator->setRules(
                $this->filterPrecognitiveRules($validator->getRulesWithoutPlaceholders())
            );
        }

        return $validator;
    }

    /**
     * Get data to be validated from the request.
     *
     * @return array
     */
    public function validationData()
    {
        return $this->all();
    }

    /**
     * Get the validation rules for this form request.
     *
     * @return array
     */
    protected function validationRules()
    {
        return method_exists($this, 'rules') ? $this->container->call([$this, 'rules']) : [];
    }

    /**
     * Determine if fields not present in rules should fail validation.
     *
     * @return bool
     */
    protected function shouldFailOnUnknownFields(): bool
    {
        $failOnUnknownFields = (new ReflectionClass($this))->getAttributes(FailOnUnknownFields::class);

        return $failOnUnknownFields !== []
            ? $failOnUnknownFields[0]->newInstance()->value
            : static::$globalFailOnUnknownFields;
    }

    /**
     * Validate that no unknown fields were sent as input.
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     * @return void
     */
    protected function validateNoUnknownFields(Validator $validator): void
    {
        $allowedKeys = array_keys($this->validationRules());

        $input = $this->isJson() ? $this->json()->all() : $this->request->all();

        foreach (array_keys(Arr::dot($input)) as $inputKey) {
            if (! $this->isKnownField($inputKey, $allowedKeys)) {
                $validator->errors()->add($inputKey, trans('validation.prohibited', [
                    'attribute' => str_replace('_', ' ', $inputKey),
                ]));
            }
        }
    }

    /**
     * Determine if the given input key is an allowed key based on the validation rules.
     *
     * @param  string  $inputKey
     * @param  array  $allowedKeys
     * @return bool
     */
    protected function isKnownField(string $inputKey, array $allowedKeys): bool
    {
        foreach ($allowedKeys as $ruleKey) {
            if ($ruleKey === $inputKey) {
                return true;
            }

            if (str_ends_with($inputKey, '_confirmation') &&
                $ruleKey === substr($inputKey, 0, -13)) {
                return true;
            }

            if (str_contains($ruleKey, '*')) {
                $pattern = '/^'.str_replace('\*', '[^.]+', preg_quote($ruleKey, '/')).'$/';

                if (preg_match($pattern, $inputKey)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Handle a failed validation attempt.
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     * @return void
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function failedValidation(Validator $validator)
    {
        $exception = $validator->getException();

        throw (new $exception($validator))
            ->errorBag($this->errorBag)
            ->redirectTo($this->getRedirectUrl());
    }

    /**
     * Get the URL to redirect to on a validation error.
     *
     * @return string
     */
    protected function getRedirectUrl()
    {
        $url = $this->redirector->getUrlGenerator();

        if ($this->redirect) {
            return $url->to($this->redirect);
        } elseif ($this->redirectRoute) {
            return $url->route($this->redirectRoute);
        } elseif ($this->redirectAction) {
            return $url->action($this->redirectAction);
        }

        return $url->previous();
    }

    /**
     * Determine if the request passes the authorization check.
     *
     * @return bool
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    protected function passesAuthorization()
    {
        if (method_exists($this, 'authorize')) {
            $result = $this->container->call([$this, 'authorize']);

            return $result instanceof Response ? $result->authorize() : $result;
        }

        return true;
    }

    /**
     * Handle a failed authorization attempt.
     *
     * @return void
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    protected function failedAuthorization()
    {
        throw new AuthorizationException;
    }

    /**
     * Get a validated input container for the validated input.
     *
     * @param  array<int, string>|null  $keys
     * @return ($keys is array ? array<string, mixed> : \Illuminate\Support\ValidatedInput)
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function safe(?array $keys = null)
    {
        return is_array($keys)
            ? $this->validator->safe()->only($keys)
            : $this->validator->safe();
    }

    /**
     * Get the validated data from the request.
     *
     * @param  array|int|string|null  $key
     * @param  mixed  $default
     * @return mixed
     */
    public function validated($key = null, $default = null)
    {
        return data_get($this->validator->validated(), $key, $default);
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages()
    {
        return [];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes()
    {
        return [];
    }

    /**
     * Enable or disable unknown-field rejection globally for all form requests.
     *
     * @param  bool  $value
     * @return void
     */
    public static function failOnUnknownFields(bool $value = true): void
    {
        static::$globalFailOnUnknownFields = $value;
    }

    /**
     * Set the Validator instance.
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     * @return $this
     */
    public function setValidator(Validator $validator)
    {
        $this->validator = $validator;

        return $this;
    }

    /**
     * Set the Redirector instance.
     *
     * @param  \Illuminate\Routing\Redirector  $redirector
     * @return $this
     */
    public function setRedirector(Redirector $redirector)
    {
        $this->redirector = $redirector;

        return $this;
    }

    /**
     * Set the container implementation.
     *
     * @param  \Illuminate\Contracts\Container\Container  $container
     * @return $this
     */
    public function setContainer(Container $container)
    {
        $this->container = $container;

        return $this;
    }

    /**
     * Flush the global state of the form request.
     *
     * @return void
     */
    public static function flushState(): void
    {
        static::$globalFailOnUnknownFields = false;
    }
}
