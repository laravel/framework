<?php

namespace Illuminate\Foundation\Http\Concerns;

use BackedEnum;
use Illuminate\Foundation\Http\Attributes\HydrateFromRequest;
use Illuminate\Foundation\Http\Attributes\WithoutInferringRules;
use Illuminate\Foundation\Http\TypedFormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionType;
use ReflectionUnionType;

trait InfersValidationRules
{
    /**
     * Infer validation rules from constructor parameter types.
     *
     * @return array<string, mixed>
     *
     * @throws \ReflectionException
     */
    protected function inferredRulesFromTypes(): array
    {
        if (($constructor = $this->reflectRequest()->getConstructor()) === null || $this->reflectRequest()->getAttributes(WithoutInferringRules::class) !== []) {
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
     * Determine if the parameter needs an UploadedFile instance.
     *
     * @return bool
     */
    protected function isFile(string $name): bool
    {
        return is_a($name, UploadedFile::class, true);
    }
}
