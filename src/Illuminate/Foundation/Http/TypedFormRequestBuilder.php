<?php

namespace Illuminate\Foundation\Http;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\Access\Response;
use Illuminate\Container\Container;
use Illuminate\Contracts\Validation\Factory as ValidationFactory;
use Illuminate\Foundation\Precognition;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Validation\Validator;
use ReflectionClass;

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

        return new $dtoClass(...$validated);
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
        $rules = [];

        // @todo add in attribute check (read from #[Rules] attribute on the requestClass)
        // @todo can we add an attribute to merge defaults set on the typed properties? like `public int $perPage = 25` will use per_page=25 if not specified
        if (method_exists($this->requestClass, 'rules')) {
            $rules = array_merge(
                $rules,
                Container::getInstance()->call(
                    [$this->requestClass, 'rules'],
                    ['request' => $this->request]
                )
            );
        }

        return $rules;
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
        (new Collection($this->reflectRequest()->getProperties(\ReflectionProperty::IS_PUBLIC)))
            ->filter(fn (\ReflectionProperty $prop) => $prop->hasDefaultValue())
            ->each(function (\ReflectionProperty $prop) use (&$data) {
                if (Arr::has($data, $name = $prop->getName())) {
                    return;
                }

                $data[$name] ??= $prop->getDefaultValue();
            });

        return $data;

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
