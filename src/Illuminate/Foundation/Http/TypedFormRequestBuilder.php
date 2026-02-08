<?php

namespace Illuminate\Foundation\Http;

use BackedEnum;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\Access\Response;
use Illuminate\Container\Container;
use Illuminate\Contracts\Validation\Factory as ValidationFactory;
use Illuminate\Foundation\Http\Attributes\MapFrom;
use Illuminate\Foundation\Http\Attributes\WithoutInferringRules;
use Illuminate\Foundation\Precognition;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Validator;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionProperty;

use function Illuminate\Support\enum_value;

/**
 * @template T of TypedFormRequest
 */
class TypedFormRequestBuilder
{
    protected Validator $validator;

    /** @var ReflectionClass<T>|null */
    protected ?ReflectionClass $reflection = null;

    /** @var list<class-string> */
    protected array $ancestors = [];

    /** @var array{rules: array, messages: array, attributes: array}|null */
    protected ?array $nestedMetadata = null;

    /**
     * @param  class-string<T>  $requestClass
     */
    public function __construct(
        protected string $requestClass,
        protected Request $request,
    ) {
    }

    /**
     * @return \ReflectionClass<T>
     *
     * @throws \ReflectionException
     */
    protected function reflectRequest(): ReflectionClass
    {
        return $this->reflection ??= new ReflectionClass($this->requestClass);
    }

    /**
     * @template TSubType
     *
     * @param  class-string<TSubType>  $class
     * @return static<TSubType>
     */
    protected function nestedBuilder(string $class): static
    {
        $builder = Container::getInstance()
            ->make(
                TypedFormRequestBuilder::class,
                ['requestClass' => $class, 'request' => $this->request]
            );
        $builder->ancestors = [...$this->ancestors, $this->requestClass];

        return $builder;
    }

    protected function fieldNameFor(ReflectionParameter $param): string
    {
        $attr = $param->getAttributes(MapFrom::class)[0] ?? null;

        return $attr ? $attr->newInstance()->name : $param->getName();
    }

    /**
     * @return T
     */
    public function build(): TypedFormRequest
    {
        $this->prepareForValidation();

        if (! $this->passesAuthorization()) {
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

        return $this->buildDto($this->validator->validated());
    }

    /**
     * @return T
     */
    protected function buildDto(array $validated): TypedFormRequest
    {
        $dtoClass = $this->requestClass;

        return new $dtoClass(...$this->castValidatedData($validated)); // @phpstan-ignore new.noConstructor (this is a requirement for now)
    }

    protected function castValidatedData(array $validated): array
    {
        $constructor = $this->reflectRequest()->getConstructor();

        if ($constructor === null) {
            return $validated;
        }

        foreach ($constructor->getParameters() as $param) {
            $fieldName = $this->fieldNameFor($param);
            $name = $param->getName();

            if (! array_key_exists($fieldName, $validated)) {
                continue;
            }

            if ($fieldName !== $name) {
                $validated[$name] = $validated[$fieldName];
                unset($validated[$fieldName]);
            }

            $type = $param->getType();

            if (! $type instanceof ReflectionNamedType || $type->isBuiltin()) {
                continue;
            }

            $typeName = $type->getName();

            if (is_subclass_of($typeName, TypedFormRequest::class)) {
                $nestedData = $this->nestedBuilder($typeName)->castValidatedData($validated[$name]);
                $validated[$name] = new $typeName(...$nestedData); // @phpstan-ignore new.noConstructor
            } elseif (is_subclass_of($typeName, BackedEnum::class)) {
                $validated[$name] = $validated[$name] !== null
                    ? $typeName::from($validated[$name])
                    : null;
            }
        }

        return $validated;
    }

    protected function prepareForValidation(): void
    {
        if (method_exists($this->requestClass, 'prepareForValidation')) {
            Container::getInstance()->call(
                [$this->requestClass, 'prepareForValidation'],
                ['request' => $this->request]
            );
        }
    }

    protected function passesAuthorization(): bool
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

    protected function failedAuthorization(): void
    {
        if (method_exists($this->requestClass, 'failedAuthorization')) {
            Container::getInstance()->call(
                [$this->requestClass, 'failedAuthorization'],
                ['request' => $this->request]
            );

            return;
        }

        throw new AuthorizationException;
    }

    protected function getValidatorInstance(): Validator
    {
        $factory = Container::getInstance()->make(ValidationFactory::class);

        $validator = method_exists($this->requestClass, 'validator')
            ? Container::getInstance()->call(
                [$this->requestClass, 'validator'],
                ['factory' => $factory]
            )
            : $this->createDefaultValidator($factory);

        if (method_exists($this->requestClass, 'after')) {
            $validator->after(Container::getInstance()->call(
                [$this->requestClass, 'after'],
                ['validator' => $validator]
            ));
        }

        return $validator;
    }

    protected function createDefaultValidator(ValidationFactory $factory): Validator
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

    protected function validationRules(): array
    {
        $rules = $this->rulesFromTypes();

        // @todo add in attribute check (read from #[Rules] attribute on the requestClass)
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
     * @return array<string, mixed>
     *
     * @throws \ReflectionException
     */
    protected function rulesFromTypes(): array
    {
        if ($this->reflectRequest()->getAttributes(WithoutInferringRules::class) !== []) {
            return [];
        }

        $constructor = $this->reflectRequest()->getConstructor();

        if ($constructor === null) {
            return [];
        }

        $rules = [];

        foreach ($constructor->getParameters() as $param) {
            $paramRules = $this->rulesForParameter($param);

            if ($paramRules !== []) {
                $rules[$this->fieldNameFor($param)] = $paramRules;
            }
        }

        return array_merge($rules, $this->nestedMetadata()['rules']);
    }

    /**
     * @return list<string|\Illuminate\Contracts\Validation\Rule|\Illuminate\Contracts\Validation\ValidatorAwareRule>
     */
    protected function rulesForParameter(ReflectionParameter $param): array
    {
        if ($param->getAttributes(WithoutInferringRules::class) !== []) {
            return [];
        }

        $type = $param->getType();

        if (! $type instanceof ReflectionNamedType) {
            return [];
        }

        $rules = [];

        // Presence: required vs sometimes vs nullable
        if ($param->isDefaultValueAvailable()) {
            $rules[] = 'sometimes';
        }

        if ($type->allowsNull()) {
            $rules[] = 'nullable';
        } elseif (! $param->isDefaultValueAvailable()) {
            $rules[] = 'required';
        }

        // Type rule
        $typeRule = match ($type->getName()) {
            'int' => 'integer',
            'float' => 'numeric',
            'string' => 'string',
            'bool' => 'boolean',
            'array' => 'array',
            default => $this->ruleForNonBuiltinType($type),
        };

        if ($typeRule !== null) {
            $rules[] = $typeRule;
        }

        return $rules;
    }

    /**
     * @return string|\Illuminate\Contracts\Validation\ValidatorAwareRule|\Illuminate\Contracts\Validation\Rule
     */
    protected function ruleForNonBuiltinType(ReflectionNamedType $type): mixed
    {
        $name = $type->getName();

        if (is_subclass_of($name, BackedEnum::class)) {
            return new Enum($name);
        }

        if (is_subclass_of($name, TypedFormRequest::class)) {
            return 'array';
        }

        return null;
    }

    /**
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

    protected function mergeRequestData(array $data): array
    {
        $constructor = $this->reflectRequest()->getConstructor();

        // Build a lookup of param name → field name for MapFrom resolution
        $fieldNames = [];

        if ($constructor !== null) {
            foreach ($constructor->getParameters() as $param) {
                $fieldNames[$param->getName()] = $this->fieldNameFor($param);
            }
        }

        (new Collection($this->reflectRequest()->getProperties(ReflectionProperty::IS_PUBLIC)))
            ->filter(fn (ReflectionProperty $prop) => $prop->hasDefaultValue())
            ->each(function (ReflectionProperty $prop) use (&$data, $fieldNames) {
                $fieldName = $fieldNames[$prop->getName()] ?? $prop->getName();

                if (Arr::has($data, $fieldName)) {
                    return;
                }
                $data[$fieldName] = $this->mapToNativeFromDefault($prop);
            });

        // Recursively merge defaults for nested TypedFormRequest properties
        if ($constructor !== null) {
            foreach ($constructor->getParameters() as $param) {
                $type = $param->getType();

                if (! $type instanceof ReflectionNamedType || $type->isBuiltin()) {
                    continue;
                }

                $typeName = $type->getName();
                $name = $this->fieldNameFor($param);

                if (is_subclass_of($typeName, TypedFormRequest::class) && isset($data[$name]) && is_array($data[$name])) {
                    $data[$name] = $this->nestedBuilder($typeName)->mergeRequestData($data[$name]);
                }
            }
        }

        return $data;
    }

    /**
     * Reflection property is assumed to have a default value.
     *
     * @param  ReflectionProperty  $prop
     * @return mixed
     */
    protected function mapToNativeFromDefault(ReflectionProperty $prop): mixed
    {
        return enum_value($prop->getDefaultValue());
    }

    protected function messages(): array
    {
        $messages = [];

        if (method_exists($this->requestClass, 'messages')) {
            $messages = Container::getInstance()->call([$this->requestClass, 'messages']);
        }

        return array_merge($messages, $this->nestedMetadata()['messages']);
    }

    protected function attributes(): array
    {
        $attributes = [];

        if (method_exists($this->requestClass, 'attributes')) {
            $attributes = Container::getInstance()->call([$this->requestClass, 'attributes']);
        }

        return array_merge($attributes, $this->nestedMetadata()['attributes']);
    }

    /**
     * @return array{rules: array<array-key, mixed>, messages: array<array-key, mixed>, attributes: array<array-key, mixed>}
     */
    protected function nestedMetadata(): array
    {
        if ($this->nestedMetadata !== null) {
            return $this->nestedMetadata;
        }

        $rules = [];
        $messages = [];
        $attributes = [];

        $constructor = $this->reflectRequest()->getConstructor();

        if ($constructor === null) {
            return $this->nestedMetadata = ['rules' => $rules, 'messages' => $messages, 'attributes' => $attributes];
        }

        foreach ($constructor->getParameters() as $param) {
            $type = $param->getType();

            if (! $type instanceof ReflectionNamedType
                || $type->isBuiltin()
                || ! is_subclass_of($type->getName(), TypedFormRequest::class)
                || in_array($type->getName(), $this->ancestors)
                || ($param->getAttributes(WithoutInferringRules::class) !== [])) {
                continue;
            }

            $name = $this->fieldNameFor($param);
            $parentIsOptional = $param->isDefaultValueAvailable() || $type->allowsNull();
            $nested = $this->nestedBuilder($type->getName());

            foreach ($nested->validationRules() as $field => $fieldRules) {
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

    protected function shouldStopOnFirstFailure(): bool
    {
        // @todo use Taylor's attribute to check
        if (method_exists($this->requestClass, 'shouldStopOnFirstFailure')) {
            return Container::getInstance()->call([$this->requestClass, 'shouldStopOnFirstFailure']);
        }

        return false;
    }

    protected function failedValidation(): void
    {
        if (method_exists($this->requestClass, 'failedValidation')) {
            Container::getInstance()->call(
                [$this->requestClass, 'failedValidation'],
                ['validator' => $this->validator]
            );

            return;
        }

        $exception = $this->validator->getException();

        throw new $exception($this->validator);
    }

    protected function passedValidation(): void
    {
        if (method_exists($this->requestClass, 'passedValidation')) {
            Container::getInstance()->call([$this->requestClass, 'passedValidation']);
        }
    }
}
