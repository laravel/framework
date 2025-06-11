<?php

namespace Illuminate\Validation;

use BackedEnum;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use UnitEnum;

trait HasValidation
{
    private array $objectData;

    private \Illuminate\Contracts\Validation\Validator|Validator $validator;

    /**
     * @throws ValidationException
     */
    public function validate(): void
    {
        if ($this->rules() === []) {
            return;
        }

        $this->objectData = $this->buildDataForValidation();
        if (! $this->validationPasses()) {
            $this->failedValidation();
        }
    }

    /**
     * Defines the validation rules for the class.
     */
    protected function rules(): array
    {
        return [];
    }

    /**
     * Defines the custom messages for validator errors.
     */
    protected function messages(): array
    {
        return [];
    }

    /**
     * Defines the custom attributes for validator errors.
     */
    protected function attributes(): array
    {
        return [];
    }

    protected function after(Validator $validator): void
    {
        // Do nothing
    }

    /**
     * @throws ValidationException
     */
    protected function failedValidation(): void
    {
        throw new ValidationException($this->validator);
    }

    private function buildDataForValidation(): array
    {
        $mappedData = [];
        $propertiesToValidated = array_keys($this->rules());
        foreach ($propertiesToValidated as $property) {
            if (
                property_exists($this, $property)
                || ($this instanceof Model && $this->hasAttribute($property))
            ) {
                $mappedData[$property] = $this->isArrayable($this->{$property})
                    ? $this->formatArrayableValue($this->{$property})
                    : $this->{$property};
            }
        }

        return $mappedData;
    }

    private function validationPasses(): bool
    {
        $this->validator = \Illuminate\Support\Facades\Validator::make(
            $this->objectData,
            $this->rules(),
            $this->messages(),
            $this->attributes()
        );

        $this->validator->after(fn (Validator $validator) => $this->after($validator));

        return $this->validator->passes();
    }

    private function isArrayable(mixed $value): bool
    {
        return is_array($value) ||
            $value instanceof Arrayable ||
            $value instanceof Collection ||
            $value instanceof Model ||
            (is_object($value) && ! ($value instanceof UploadedFile));
    }

    private function formatArrayableValue(mixed $value): array|int|string
    {
        return match (true) {
            is_array($value) => $value,
            $value instanceof BackedEnum => $value->value,
            $value instanceof UnitEnum => $value->name,
            $value instanceof Carbon || $value instanceof CarbonImmutable => $value->toISOString(true),
            $value instanceof Collection => $this->transformCollectionToArray($value),
            $value instanceof Arrayable => $value->toArray(),
            is_object($value) => (array) $value,
            default => [],
        };
    }

    private function transformCollectionToArray(Collection $collection): array
    {
        return $collection->map(fn ($item) => $this->isArrayable($item)
            ? $this->formatArrayableValue($item)
            : $item
        )->toArray();
    }
}
