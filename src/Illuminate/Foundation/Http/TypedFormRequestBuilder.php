<?php

namespace Illuminate\Foundation\Http;

use BackedEnum;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\Access\Response;
use Illuminate\Container\Container;
use Illuminate\Contracts\Validation\Factory as ValidationFactory;
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

    /**
     * @param  class-string<T>  $requestClass
     * @param  Request  $request
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
     * @return T
     */
    public function handle(): TypedFormRequest
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

        // @todo validated() only works on data which has been validated... they SHOULD always validate the data but it's possible they do not
        return $this->buildDto($this->validator->validated());
    }

    /**
     * @return T
     */
    protected function buildDto(array $validated): TypedFormRequest
    {
        // @todo add ability to map request to DTO params
        // @todo handle nested objects
        $dtoClass = $this->requestClass;

        return new $dtoClass(...$this->castValidatedData($validated)); // @phpstan-ignore new.noConstructor (this is a requirement for now)
    }

    protected function castValidatedData(array $validated): array
    {
        return static::castValidatedDataFor($validated, $this->requestClass);
    }

    /**
     * @param  class-string<TypedFormRequest>  $class
     */
    protected static function castValidatedDataFor(array $validated, string $class): array
    {
        $constructor = (new ReflectionClass($class))->getConstructor();

        if ($constructor === null) {
            return $validated;
        }

        foreach ($constructor->getParameters() as $param) {
            $name = $param->getName();

            if (! array_key_exists($name, $validated)) {
                continue;
            }

            $type = $param->getType();

            if (! $type instanceof ReflectionNamedType || $type->isBuiltin()) {
                continue;
            }

            $typeName = $type->getName();

            if (is_subclass_of($typeName, TypedFormRequest::class)) {
                $nestedData = static::castValidatedDataFor($validated[$name], $typeName);
                $validated[$name] = new $typeName(...$nestedData);
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

        if (method_exists($this->requestClass, 'validator')) {
            $validator = Container::getInstance()->call(
                [$this->requestClass, 'validator'],
                ['factory' => $factory]
            );
        } else {
            $validator = $this->createDefaultValidator($factory);
        }

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
        return static::rulesFromTypesFor($this->requestClass);
    }

    /**
     * @param  class-string<TypedFormRequest>  $class
     * @param  string  $prefix
     * @return array<string, mixed>
     */
    protected static function rulesFromTypesFor(string $class, string $prefix = ''): array
    {
        $constructor = (new ReflectionClass($class))->getConstructor();

        if ($constructor === null) {
            return [];
        }

        $rules = [];

        foreach ($constructor->getParameters() as $param) {
            $paramRules = static::rulesForParameterStatic($param);
            $key = $prefix.$param->getName();

            if ($paramRules !== []) {
                $rules[$key] = $paramRules;
            }

            // Recursively collect nested TypedFormRequest rules
            $type = $param->getType();

            if ($type instanceof ReflectionNamedType
                && ! $type->isBuiltin()
                && is_subclass_of($type->getName(), TypedFormRequest::class)) {
                $parentIsOptional = $param->isDefaultValueAvailable() || $type->allowsNull();
                $nestedRules = static::nestedRulesFor($type->getName(), $key.'.', $parentIsOptional ? $key : null);

                foreach ($nestedRules as $nestedKey => $nestedFieldRules) {
                    $rules[$nestedKey] = $nestedFieldRules;
                }
            }
        }

        return $rules;
    }

    /**
     * @param  class-string<TypedFormRequest>  $class
     * @param  string  $prefix
     * @param  string|null  $optionalParentKey  When set, 'required' rules become 'required_with:<key>'
     * @return array<string, mixed>
     */
    protected static function nestedRulesFor(string $class, string $prefix, ?string $optionalParentKey = null): array
    {
        $rules = static::rulesFromTypesFor($class, $prefix);

        if (method_exists($class, 'rules')) {
            $userRules = Container::getInstance()->call([$class, 'rules']);

            foreach ($userRules as $field => $fieldRules) {
                $key = $prefix.$field;
                $rules[$key] = array_merge($rules[$key] ?? [], Arr::wrap($fieldRules));
            }
        }

        if ($optionalParentKey !== null) {
            foreach ($rules as $key => $fieldRules) {
                $rules[$key] = array_map(
                    fn ($rule) => $rule === 'required' ? "required_with:{$optionalParentKey}" : $rule,
                    $fieldRules,
                );
            }
        }

        return $rules;
    }

    protected function rulesForParameter(ReflectionParameter $param): array
    {
        return static::rulesForParameterStatic($param);
    }

    protected static function rulesForParameterStatic(ReflectionParameter $param): array
    {
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
            default => static::ruleForNonBuiltinTypeStatic($type),
        };

        if ($typeRule !== null) {
            $rules[] = $typeRule;
        }

        return $rules;
    }

    protected function ruleForNonBuiltinType(ReflectionNamedType $type): mixed
    {
        return static::ruleForNonBuiltinTypeStatic($type);
    }

    protected static function ruleForNonBuiltinTypeStatic(ReflectionNamedType $type): mixed
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

    protected function validationData(): array
    {
        // @todo can we add an attribute to merge defaults?
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
        return static::mergeRequestDataFor($data, $this->requestClass);
    }

    /**
     * @param  class-string<TypedFormRequest>  $class
     */
    protected static function mergeRequestDataFor(array $data, string $class): array
    {
        $reflection = new ReflectionClass($class);

        (new Collection($reflection->getProperties(ReflectionProperty::IS_PUBLIC)))
            ->filter(fn (ReflectionProperty $prop) => $prop->hasDefaultValue())
            ->each(function (ReflectionProperty $prop) use (&$data) {
                if (Arr::has($data, $name = $prop->getName())) {
                    return;
                }
                $data[$name] = static::mapToNativeFromDefaultStatic($prop);
            });

        // Recursively merge defaults for nested TypedFormRequest properties
        $constructor = $reflection->getConstructor();

        if ($constructor === null) {
            return $data;
        }

        foreach ($constructor->getParameters() as $param) {
            $type = $param->getType();

            if (! $type instanceof ReflectionNamedType || $type->isBuiltin()) {
                continue;
            }

            $typeName = $type->getName();
            $name = $param->getName();

            if (is_subclass_of($typeName, TypedFormRequest::class) && isset($data[$name]) && is_array($data[$name])) {
                $data[$name] = static::mergeRequestDataFor($data[$name], $typeName);
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
        // @todo probably have to see what the getDefaultValue returns. if it's an object then we're going to need to check if it can be translated into something. Maybe check Arrayble and check if it's a TypedFormRequest
        return enum_value($prop->getDefaultValue());
    }

    protected static function mapToNativeFromDefaultStatic(ReflectionProperty $prop): mixed
    {
        // @todo probably have to see what the getDefaultValue returns. if it's an object then we're going to need to check if it can be translated into something. Maybe check Arrayble and check if it's a TypedFormRequest
        return enum_value($prop->getDefaultValue());
    }

    protected function messages(): array
    {
        if (method_exists($this->requestClass, 'messages')) {
            return Container::getInstance()->call([$this->requestClass, 'messages']);
        }

        return [];
    }

    protected function attributes(): array
    {
        if (method_exists($this->requestClass, 'attributes')) {
            return Container::getInstance()->call([$this->requestClass, 'attributes']);
        }

        return [];
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
