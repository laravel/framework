<?php

namespace Illuminate\Foundation\Http;

use BackedEnum;
use Carbon\CarbonImmutable;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\Access\Response;
use Illuminate\Container\Container;
use Illuminate\Contracts\Validation\Factory as ValidationFactory;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Foundation\Http\Attributes\HydrateFromRequest;
use Illuminate\Foundation\Http\Attributes\MapFrom;
use Illuminate\Foundation\Http\Attributes\StopOnFirstFailure;
use Illuminate\Foundation\Http\Attributes\WithoutInferringRules;
use Illuminate\Foundation\Precognition;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionType;
use ReflectionUnionType;
use SplFileInfo;
use stdClass;

use function Illuminate\Support\enum_value;

/**
 * @template T of TypedFormRequest
 */
class TypedFormRequestFactory
{
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
     * The cached nested validation metadata.
     *
     * @var array{rules: array<array-key, mixed>, messages: array<array-key, mixed>, attributes: array<array-key, mixed>}
     */
    protected array $nestedMetadata;

    /**
     * The cached HydrateFromRequest attribute checks.
     *
     * @var array<class-string, bool>
     */
    protected array $hydrateFromRequestCache = [];

    /**
     * The cached nested factories.
     *
     * @var array<class-string, static>
     */
    protected array $nestedFactories = [];

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
     */
    public function __construct(
        protected string $requestClass,
        protected Request $request,
    ) {
    }

    /**
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
     * Get the first union branch that should be hydrated from an array payload.
     *
     * @return class-string|null
     */
    protected function nestedHydrationClassFromUnion(ReflectionUnionType $type, ReflectionParameter $param): ?string
    {
        foreach ($type->getTypes() as $named) {
            if ($named->getName() === 'null' || $named->isBuiltin()) {
                continue;
            }

            $class = $named->getName();

            if (in_array($class, $this->ancestors)) {
                continue;
            }

            if (is_subclass_of($class, TypedFormRequest::class) || $this->shouldHydrateParameter($param, $class)) {
                return $class;
            }
        }

        return null;
    }

    /**
     * @template TTypedFormRequest of TypedFormRequest
     *
     * @param  class-string<TTypedFormRequest>  $class
     * @return static<TTypedFormRequest>
     */
    protected function nestedFactory(string $class): static
    {
        if (isset($this->nestedFactories[$class])) {
            return $this->nestedFactories[$class];
        }

        $builder = Container::getInstance()
            ->make(
                TypedFormRequestFactory::class,
                ['requestClass' => $class, 'request' => $this->request]
            );
        $builder->ancestors = [...$this->ancestors, $this->requestClass];

        $this->nestedFactories[$class] = $builder;

        return $builder;
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
     * Cast validated data into constructor arguments for the request class.
     *
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     *
     * @throws \ReflectionException
     */
    protected function castValidatedData(array $validated): array
    {
        if (($constructor = $this->reflectRequest()->getConstructor()) === null) {
            return $validated;
        }

        $arguments = [];

        foreach ($constructor->getParameters() as $param) {
            $fieldName = $this->fieldNameFor($param);
            $name = $param->getName();

            if (! Arr::has($validated, $fieldName)) {
                continue;
            }

            $value = Arr::get($validated, $fieldName);

            if ($value === null) {
                $arguments[$name] = null;

                continue;
            }

            $type = $param->getType();

            if ($type instanceof ReflectionUnionType) {
                $nestedRequestClass = $this->nestedHydrationClassFromUnion($type, $param);

                if ($nestedRequestClass !== null && is_array($value)) {
                    $arguments[$name] = $this->instantiateFromValidatedArray(
                        $nestedRequestClass,
                        $this->ensureArrayValue($fieldName, $value)
                    );
                } else {
                    $arguments[$name] = $value;
                }

                continue;
            }

            if (! $type instanceof ReflectionNamedType) {
                $arguments[$name] = $value;

                continue;
            }

            $typeName = $type->getName();
            if ($type->isBuiltin()) {
                if ($typeName === 'object') {
                    $arguments[$name] = $this->castBuiltinObjectValue($value);
                } else {
                    $arguments[$name] = $value;
                }

                continue;
            }

            if ($this->isDateObjectType($typeName)) {
                $arguments[$name] = $this->castDateValue($typeName, $value);
            } elseif (is_a($typeName, stdClass::class, true)) {
                $arguments[$name] = $this->castBuiltinObjectValue($value);
            } elseif ($this->shouldHydrateParameter($param, $typeName) || is_subclass_of($typeName, TypedFormRequest::class)) {
                $arguments[$name] = $this->instantiateFromValidatedArray(
                    $typeName,
                    $this->ensureArrayValue($fieldName, $value)
                );
            } elseif (is_subclass_of($typeName, BackedEnum::class)) {
                $arguments[$name] = $typeName::from($value);
            } elseif (is_a($typeName, Collection::class, true)) {
                if ($value instanceof $typeName) {
                    $arguments[$name] = $value;
                } else {
                    $arguments[$name] = new $typeName($this->ensureArrayValue($fieldName, $value));
                }
            } else {
                $arguments[$name] = $value;
            }
        }

        return $arguments;
    }

    /**
     * Ensure the given value is an array payload or throw a validation exception.
     *
     * @template TValue of array<array-key, mixed>
     *
     * @param  string  $fieldName
     * @param  TValue|mixed  $value
     * @return array<array-key, mixed>
     *
     * @phpstan-return ($value is array<array-key, mixed> ? TValue : never)
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function ensureArrayValue(string $fieldName, mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        throw ValidationException::withMessages([
            $fieldName => ["The {$fieldName} field must be an array."],
        ]);
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
     * Cast an "object" builtin value into a stdClass instance when appropriate.
     */
    protected function castBuiltinObjectValue(mixed $value): mixed
    {
        return is_array($value) ? (object) $value : $value;
    }

    /**
     * Call the request's prepareForValidation hook if it exists.
     */
    protected function prepareForValidation(): void
    {
        if (method_exists($this->requestClass, 'prepareForValidation')) {
            Container::getInstance()->call(
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
            $result = Container::getInstance()->call(
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
            Container::getInstance()->call(
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
        $factory = Container::getInstance()->make(ValidationFactory::class);

        $validator = method_exists($this->requestClass, 'validator')
            ? Container::getInstance()->call(
                [$this->requestClass, 'validator'],
                ['factory' => $factory]
            )
            : $this->createDefaultValidator($factory);

        if (method_exists($this->requestClass, 'withValidator')) {
            $this->requestClass::withValidator($validator);
        }

        if (method_exists($this->requestClass, 'after')) {
            $validator->after(Container::getInstance()->call(
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
            $userRules = Container::getInstance()->call(
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
     * Infer validation rules from constructor parameter types.
     *
     * @return array<string, mixed>
     *
     * @throws \ReflectionException
     */
    protected function inferredRulesFromTypes(): array
    {
        if ($this->reflectRequest()->getAttributes(WithoutInferringRules::class) !== []) {
            return [];
        }

        if (($constructor = $this->reflectRequest()->getConstructor()) === null) {
            return [];
        }

        $rules = [];

        foreach ($constructor->getParameters() as $param) {
            $paramRules = $this->rulesForParameter($param);

            if ($paramRules !== []) {
                $rules[$this->fieldNameFor($param)] = $paramRules;
            }
        }

        return $rules;
    }

    /**
     * Infer validation rules for the given constructor parameter.
     *
     * @return list<string|\Illuminate\Contracts\Validation\Rule|\Illuminate\Contracts\Validation\ValidatorAwareRule>
     */
    protected function rulesForParameter(ReflectionParameter $param): array
    {
        if ($param->getAttributes(WithoutInferringRules::class) !== []) {
            return [];
        }

        $type = $param->getType();

        if (! $type instanceof ReflectionType) {
            return [];
        }

        $rules = [];

        if ($param->isDefaultValueAvailable()) {
            $rules[] = 'sometimes';
        } elseif ($type->allowsNull()) {
            $rules[] = 'present';
        } else {
            $rules[] = 'required';
        }

        if ($type->allowsNull()) {
            $rules[] = 'nullable';
        }

        if ($param->getAttributes(HydrateFromRequest::class) !== []) {
            $typeRule = 'array';
        } else {
            $typeRule = $type instanceof ReflectionUnionType
                ? $this->ruleForUnionType($type)
                : ($type instanceof ReflectionNamedType ? $this->ruleForNamedType($type) : null);
        }

        if ($typeRule !== null) {
            $rules[] = $typeRule;
        }

        return $rules;
    }

    /**
     * Infer a validation rule for a named type.
     *
     * @return string|\Illuminate\Contracts\Validation\ValidatorAwareRule|\Illuminate\Contracts\Validation\Rule|null
     */
    protected function ruleForNamedType(ReflectionNamedType $type): mixed
    {
        $name = $type->getName();

        if ($type->isBuiltin()) {
            return match ($name) {
                'int' => 'integer',
                'float' => 'numeric',
                'string' => 'string',
                'bool' => 'boolean',
                'true' => 'accepted',
                'false' => 'declined',
                'array', 'object', 'iterable' => 'array',
                default => null,
            };
        }

        return $this->ruleForNonBuiltinType($type);
    }

    /**
     * Infer a validation rule for a union type.
     *
     * @return \Illuminate\Contracts\Validation\ValidatorAwareRule|\Illuminate\Contracts\Validation\Rule|null
     */
    protected function ruleForUnionType(ReflectionUnionType $type): mixed
    {
        $branches = [];

        foreach ($type->getTypes() as $named) {
            if ($named->getName() === 'null') {
                continue;
            }

            $branchRule = $this->ruleForNamedType($named);

            if ($branchRule === null) {
                return null;
            }

            $branches[] = [$branchRule];
        }

        if ($branches === []) {
            return null;
        }

        return Rule::anyOf($branches);
    }

    /**
     * Infer a validation rule for a non-builtin named type.
     *
     * @return string|\Illuminate\Contracts\Validation\ValidatorAwareRule|\Illuminate\Contracts\Validation\Rule
     */
    protected function ruleForNonBuiltinType(ReflectionNamedType $type): mixed
    {
        $name = $type->getName();

        if ($this->shouldHydrateFromRequest($name)) {
            return 'array';
        }

        if (is_subclass_of($name, BackedEnum::class)) {
            return new Enum($name);
        }

        if ($this->isDateObjectType($name)) {
            return 'date';
        }

        if (is_subclass_of($name, TypedFormRequest::class) || is_a($name, Collection::class, true) || is_a($name, stdClass::class, true)) {
            return 'array';
        }

        if ($this->isFile($name)) {
            return 'file';
        }

        return null;
    }

    /**
     * Determine if the given class name is a date object type.
     *
     * @param  class-string  $name
     */
    protected function isDateObjectType(string $name): bool
    {
        return is_a($name, DateTimeInterface::class, true);
    }

    /**
     * @return bool
     */
    protected function isFile(string $name): bool
    {
        return is_a($name, SplFileInfo::class, true);
    }

    /**
     * Cast the given value to the requested date object type.
     *
     * @param  class-string  $typeName  The date object class name.
     * @param  mixed  $value  The validated value.
     */
    protected function castDateValue(string $typeName, mixed $value): ?DateTimeInterface
    {
        if ($value === null || ($value instanceof DateTimeInterface && $value instanceof $typeName)) {
            return $value;
        }

        $parsed = Date::parse($value);

        return match (true) {
            $typeName === DateTimeInterface::class => $parsed,
            $typeName === DateTime::class => $parsed->toDateTime(),
            $typeName === DateTimeImmutable::class => $parsed->toDateTimeImmutable(),
            is_a($typeName, CarbonImmutable::class, true) => CarbonImmutable::instance($parsed),
            default => $parsed,
        };
    }

    /**
     * Get the validation data for the request.
     *
     * @return array<string, mixed>
     */
    protected function validationData(): array
    {
        // @todo add in an attribute that will read the docblocks for things like `int<1, 100>` and convert them into the rules
        if (method_exists($this->requestClass, 'validationData')) {
            return Container::getInstance()->call(
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
     * Determine if a given class should be hydrated from request data because
     * it has the HydrateFromRequest applied at the class-level.
     *
     * @param  class-string  $class
     */
    protected function shouldHydrateFromRequest(string $class): bool
    {
        if (isset($this->hydrateFromRequestCache[$class])) {
            return $this->hydrateFromRequestCache[$class];
        }

        $reflection = new ReflectionClass($class);

        return $this->hydrateFromRequestCache[$class] = $reflection->getAttributes(HydrateFromRequest::class) !== [];
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
     * Convert a reflected default value to a native value.
     *
     * @template TValue
     *
     * @param  TValue  $value
     * @return mixed
     *
     * @phpstan-return ($value is empty ? null : ($value is \BackedEnum ? value-of<TValue> : ($value is \UnitEnum ? string : TValue)))
     */
    protected function mapToNativeFromDefaultValue(mixed $value): mixed
    {
        return enum_value($value);
    }

    /**
     * Get the validation messages for the request.
     *
     * @return array<string, mixed>
     */
    protected function messages(): array
    {
        $messages = [];

        if (method_exists($this->requestClass, 'messages')) {
            $messages = Container::getInstance()->call([$this->requestClass, 'messages']);
        }

        return array_merge($messages, $this->nestedMetadata()['messages']);
    }

    /**
     * Get the validation attributes for the request.
     *
     * @return array<string, mixed>
     */
    protected function attributes(): array
    {
        $attributes = [];

        if (method_exists($this->requestClass, 'attributes')) {
            $attributes = Container::getInstance()->call([$this->requestClass, 'attributes']);
        }

        return array_merge($attributes, $this->nestedMetadata()['attributes']);
    }

    /**
     * Get validation metadata for nested hydrated objects.
     *
     * @return array{rules: array<array-key, mixed>, messages: array<array-key, mixed>, attributes: array<array-key, mixed>}
     */
    protected function nestedMetadata(): array
    {
        if (isset($this->nestedMetadata)) {
            return $this->nestedMetadata;
        }

        if (($constructor = $this->reflectRequest()->getConstructor()) === null) {
            return $this->nestedMetadata = ['rules' => [], 'messages' => [], 'attributes' => []];
        }

        $rules = [];
        $messages = [];
        $attributes = [];

        foreach ($constructor->getParameters() as $param) {
            $type = $param->getType();

            if ($param->getAttributes(WithoutInferringRules::class) !== []) {
                continue;
            }

            $name = $this->fieldNameFor($param);
            $parentIsOptional = $param->isDefaultValueAvailable() || ($type?->allowsNull() ?? false);

            if ($type instanceof ReflectionNamedType) {
                if ($type->isBuiltin()
                    || in_array($type->getName(), $this->ancestors)
                    || (! is_subclass_of($type->getName(), TypedFormRequest::class) && ! $this->shouldHydrateParameter($param, $type->getName()))) {
                    continue;
                }

                $nested = $this->nestedFactory($type->getName());
                $excludeRule = null;
            } elseif ($type instanceof ReflectionUnionType) {
                $nestedRequestClass = $this->nestedHydrationClassFromUnion($type, $param);

                if ($nestedRequestClass === null) {
                    continue;
                }

                $nested = $this->nestedFactory($nestedRequestClass);
                $excludeRule = Rule::excludeIf(fn () => ! is_array(Arr::get($this->validationData(), $name)));
            } else {
                continue;
            }

            foreach ($nested->validationRules() as $field => $fieldRules) {
                if (isset($excludeRule)) {
                    array_unshift($fieldRules, $excludeRule);
                }

                if ($parentIsOptional) {
                    $fieldRules = array_map(
                        static fn ($rule) => $rule === 'required' ? "required_with:$name" : $rule,
                        $fieldRules,
                    );
                }

                $rules["$name.$field"] = $fieldRules;
            }

            foreach ($nested->messages() as $key => $message) {
                $messages["$name.$key"] = $message;
            }

            foreach ($nested->attributes() as $key => $attribute) {
                $attributes["$name.$key"] = $attribute;
            }
        }

        return $this->nestedMetadata = [
            'rules' => $rules,
            'messages' => $messages,
            'attributes' => $attributes,
        ];
    }

    /**
     * Determine if the request should stop on first validation failure.
     */
    protected function shouldStopOnFirstFailure(): bool
    {
        if (method_exists($this->requestClass, 'shouldStopOnFirstFailure')) {
            return (bool) Container::getInstance()->call([$this->requestClass, 'shouldStopOnFirstFailure']);
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
            Container::getInstance()->call(
                [$this->requestClass, 'failedValidation'],
                ['validator' => $this->validator]
            );
        }

        $exception = $this->validator->getException();

        throw new $exception($this->validator);
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
