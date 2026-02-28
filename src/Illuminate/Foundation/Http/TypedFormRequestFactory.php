<?php

namespace Illuminate\Foundation\Http;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Validation\Factory as ValidationFactory;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Foundation\Http\Attributes\HydrateFromRequest;
use Illuminate\Foundation\Http\Attributes\MapFrom;
use Illuminate\Foundation\Http\Attributes\RedirectTo;
use Illuminate\Foundation\Http\Attributes\RedirectToRoute;
use Illuminate\Foundation\Http\Attributes\StopOnFirstFailure;
use Illuminate\Foundation\Http\Concerns\CastsValidatedData;
use Illuminate\Foundation\Http\Concerns\InfersValidationRules;
use Illuminate\Foundation\Http\Concerns\ResolvesNestedMetadata;
use Illuminate\Foundation\Precognition;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionParameter;

/**
 * @template T of TypedFormRequest
 */
class TypedFormRequestFactory
{
    use CastsValidatedData;
    use InfersValidationRules;
    use ResolvesNestedMetadata;

    /**
     * The validator instance for this request.
     */
    protected Validator $validator;

    /**
     * The reflected request class.
     *
     * @var ReflectionClass<T>
     */
    protected ReflectionClass $reflection;

    /**
     * The ancestor request classes being hydrated.
     *
     * Used to prevent recursive hydration loops.
     *
     * @var list<class-string>
     */
    protected array $ancestors = [];

    /**
     * Whether authorization checks should run.
     *
     * @var bool
     */
    protected bool $withAuthorization = true;

    /**
     * Create a new TypedFormRequest factory instance.
     *
     * @param  class-string<T>  $requestClass  The request class being built.
     * @param  Request  $request  The underlying HTTP request instance.
     * @param  Container  $container  The container instance.
     */
    public function __construct(
        protected string $requestClass,
        protected Request $request,
        protected Container $container,
    ) {
    }

    /**
     * Set authorization checks to run against the request. Useful for performing
     * validation checks outside of the request life-cycle.
     *
     * @return $this
     */
    public function withAuthorization(bool $withAuthorization): static
    {
        $this->withAuthorization = $withAuthorization;

        return $this;
    }

    /**
     * Build and validate the TypedFormRequest instance.
     *
     * @return T
     */
    public function build(): TypedFormRequest
    {
        $this->prepareForValidation();

        if ($this->withAuthorization && ! $this->passesAuthorization()) {
            $this->failedAuthorization();
        }

        $this->validator = $this->getValidatorInstance();

        if ($this->request->isPrecognitive()) {
            $this->validator->after(Precognition::afterValidationHook($this->request));
        }

        if ($this->validator->fails()) {
            $this->failedValidation();
        }

        $this->passedValidation();

        return $this->buildTypedFormRequest($this->validator->validated());
    }

    /**
     * Resolve the validation field name for the given parameter.
     *
     * @param  ReflectionParameter  $param  The reflected parameter.
     */
    protected function fieldNameFor(ReflectionParameter $param): string
    {
        $attr = $param->getAttributes(MapFrom::class)[0] ?? null;

        return $attr === null ? $param->getName() : $attr->newInstance()->name;
    }

    /**
     * Instantiate the TypedFormRequest with cast/normalized validated data.
     *
     * @param  array<string, mixed>  $validated  The validated request data.
     * @return T
     */
    protected function buildTypedFormRequest(array $validated): TypedFormRequest
    {
        $requestClass = $this->requestClass;

        return new $requestClass(...$this->castValidatedData($validated));
    }

    /**
     * Instantiate a nested object from a validated array payload.
     *
     * @param  class-string  $class
     * @param  array<array-key, mixed>  $value
     */
    protected function instantiateFromValidatedArray(string $class, array $value): object
    {
        return new $class(...$this->nestedFactory($class)->castValidatedData($value));
    }

    /**
     * Call the request's prepareForValidation hook if it exists.
     */
    protected function prepareForValidation(): void
    {
        if (method_exists($this->requestClass, 'prepareForValidation')) {
            $this->container->call(
                [$this->requestClass, 'prepareForValidation'],
                ['request' => $this->request]
            );
        }
    }

    /**
     * Determine if the request passes authorization.
     */
    protected function passesAuthorization()
    {
        if (method_exists($this->requestClass, 'authorize')) {
            $result = $this->container->call(
                [$this->requestClass, 'authorize'],
                ['request' => $this->request]
            );

            return $result instanceof Response ? $result->authorize() : $result;
        }

        return true;
    }

    /**
     * Handle a failed authorization attempt.
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    protected function failedAuthorization(): never
    {
        if (method_exists($this->requestClass, 'failedAuthorization')) {
            $this->container->call(
                [$this->requestClass, 'failedAuthorization'],
                ['request' => $this->request]
            );
        }

        throw new AuthorizationException;
    }

    /**
     * Get the validator instance for the request.
     */
    protected function getValidatorInstance(): ValidatorContract
    {
        $factory = $this->container->make(ValidationFactory::class);

        $validator = method_exists($this->requestClass, 'validator')
            ? $this->container->call(
                [$this->requestClass, 'validator'],
                ['factory' => $factory]
            )
            : $this->createDefaultValidator($factory);

        if (method_exists($this->requestClass, 'withValidator')) {
            $this->requestClass::withValidator($validator);
        }

        if (method_exists($this->requestClass, 'after')) {
            $validator->after($this->container->call(
                [$this->requestClass, 'after'],
                ['validator' => $validator]
            ));
        }

        return $validator;
    }

    /**
     * Create the default validator for the request.
     */
    protected function createDefaultValidator(ValidationFactory $factory): ValidatorContract
    {
        $validator = $factory->make(
            $this->validationData(),
            $this->validationRules(),
            $this->messages(),
            $this->attributes(),
        )->stopOnFirstFailure($this->shouldStopOnFirstFailure());

        if ($this->request->isPrecognitive()) {
            $validator->setRules(
                $this->request->filterPrecognitiveRules($validator->getRulesWithoutPlaceholders())
            );
        }

        return $validator;
    }

    /**
     * Get the fully merged validation rules for the request.
     *
     * @return array<string, mixed>
     */
    protected function validationRules(): array
    {
        // Combine the inferred rules from the constructor arguments
        // with the validation rules for nested objects.
        $rules = array_merge($this->inferredRulesFromTypes(), $this->nestedMetadata()['rules']);

        if (method_exists($this->requestClass, 'rules')) {
            $userRules = $this->container->call(
                [$this->requestClass, 'rules'],
                ['request' => $this->request]
            );

            foreach ($userRules as $field => $fieldRules) {
                $rules[$field] = array_merge($rules[$field] ?? [], Arr::wrap($fieldRules));
            }
        }

        return $rules;
    }

    /**
     * Get the validation data for the request.
     *
     * @return array<string, mixed>
     */
    protected function validationData(): array
    {
        if (method_exists($this->requestClass, 'validationData')) {
            return $this->container->call(
                [$this->requestClass, 'validationData'],
                ['request' => $this->request]
            );
        }

        return $this->mergeRequestData($this->request->all());
    }

    /**
     * Merge request data with defaults for missing fields.
     *
     * @param  array<string, mixed>  $data  The input data.
     * @return array<string, mixed>
     */
    protected function mergeRequestData(array $data): array
    {
        if (($constructor = $this->reflectRequest()->getConstructor()) === null) {
            return $data;
        }

        foreach ($constructor->getParameters() as $param) {
            $fieldName = $this->fieldNameFor($param);

            // If no data for this field was included in the request, use the
            // default value defined for that parameter from the object.
            if ($param->isDefaultValueAvailable() && ! Arr::has($data, $fieldName)) {
                Arr::set($data, $fieldName, $this->mapToNativeFromDefaultValue($param->getDefaultValue()));
            }

            $type = $param->getType();

            if (! $type instanceof ReflectionNamedType || $type->isBuiltin()) {
                continue;
            }

            $typeName = $type->getName();

            if (! is_subclass_of($typeName, TypedFormRequest::class)) {
                continue;
            }

            if (Arr::has($data, $fieldName) && is_array($value = Arr::get($data, $fieldName))) {
                Arr::set($data, $fieldName, $this->nestedFactory($typeName)->mergeRequestData($value));
            }
        }

        return $data;
    }


    /**
     * Get the reflected TypedFormRequest class.
     *
     * @return \ReflectionClass<T>
     *
     * @throws \ReflectionException
     */
    protected function reflectRequest(): ReflectionClass
    {
        return $this->reflection ??= new ReflectionClass($this->requestClass);
    }

    /**
     * Determine if the given constructor parameter should be hydrated from request data.
     *
     * @param  ReflectionParameter  $param
     * @param  class-string  $class
     */
    protected function shouldHydrateParameter(ReflectionParameter $param, string $class): bool
    {
        return $param->getAttributes(HydrateFromRequest::class) !== [] || $this->shouldHydrateFromRequest($class);
    }

    /**
     * Determine if the request should stop on first validation failure.
     */
    protected function shouldStopOnFirstFailure(): bool
    {
        if (method_exists($this->requestClass, 'shouldStopOnFirstFailure')) {
            return (bool) $this->container->call([$this->requestClass, 'shouldStopOnFirstFailure']);
        }

        if ($this->reflectRequest()->getAttributes(StopOnFirstFailure::class) !== []) {
            return true;
        }

        return false;
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(): never
    {
        if (method_exists($this->requestClass, 'failedValidation')) {
            $this->container->call(
                [$this->requestClass, 'failedValidation'],
                ['validator' => $this->validator]
            );
        }

        $exceptionClass = $this->validator->getException();

        throw $this->decorateException(new $exceptionClass($this->validator));
    }

    /**
     * Decorate the validation exception with redirect information from class attributes.
     */
    protected function decorateException(ValidationException $exception): ValidationException
    {
        $reflection = $this->reflectRequest();

        if ($redirect = $reflection->getAttributes(RedirectTo::class)[0] ?? null) {
            $exception->redirectTo($redirect->newInstance()->url);
        } elseif ($route = $reflection->getAttributes(RedirectToRoute::class)[0] ?? null) {
            $exception->redirectTo(url()->route($route->newInstance()->route));
        }

        return $exception;
    }

    /**
     * Call the request's passedValidation hook if it exists.
     */
    protected function passedValidation(): void
    {
        if (method_exists($this->requestClass, 'passedValidation')) {
            $this->requestClass::passedValidation($this->request);
        }
    }
}
