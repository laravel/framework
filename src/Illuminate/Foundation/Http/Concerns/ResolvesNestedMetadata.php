<?php

namespace Illuminate\Foundation\Http\Concerns;

use Illuminate\Foundation\Http\Attributes\WithoutInferringRules;
use Illuminate\Foundation\Http\TypedFormRequest;
use Illuminate\Foundation\Http\TypedFormRequestFactory;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use ReflectionNamedType;
use ReflectionUnionType;

trait ResolvesNestedMetadata
{
    /**
     * The cached nested validation metadata.
     *
     * @var array{rules: array<array-key, mixed>, messages: array<array-key, mixed>, attributes: array<array-key, mixed>}
     */
    protected array $nestedMetadata;

    /**
     * The cached nested factories.
     *
     * @var array<class-string, static>
     */
    protected array $nestedFactories = [];

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

        $builder = $this->container
            ->make(
                TypedFormRequestFactory::class,
                ['requestClass' => $class, 'request' => $this->request, 'container' => $this->container]
            );
        $builder->ancestors = [...$this->ancestors, $this->requestClass];

        $this->nestedFactories[$class] = $builder;

        return $builder;
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
            $messages = $this->container->call([$this->requestClass, 'messages']);
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
            $attributes = $this->container->call([$this->requestClass, 'attributes']);
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
}
