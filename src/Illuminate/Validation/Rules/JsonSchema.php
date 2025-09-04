<?php

namespace Illuminate\Validation\Rules;

use Exception;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\JsonSchema\JsonSchema as Schema;
use JsonException;
use Opis\JsonSchema\Errors\ValidationError;
use Opis\JsonSchema\Validator;

class JsonSchema implements Rule
{
    /**
     * The validation error message.
     */
    protected ?string $errorMessage = null;

    /**
     * Create a new JSON schema validation rule.
     */
    public function __construct(protected Schema $schema)
    {
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     */
    public function passes($attribute, $value): bool
    {
        $this->errorMessage = null;

        // Normalize the data to what Opis expects
        $data = $this->normalizeData($value);

        if ($data === null && $this->errorMessage !== null) {
            return false;
        }

        try {
            $result = (new Validator)->validate($data, $this->schema->toString());

            if ($result->isValid()) {
                return true;
            }

            $this->errorMessage = $this->formatValidationError($result->error());
        } catch (Exception $e) {
            $this->errorMessage = "Schema validation error: {$e->getMessage()}";
        }

        return false;
    }

    /**
     * Get the validation error message.
     */
    public function message(): string
    {
        return $this->errorMessage ?? 'The :attribute does not match the required schema.';
    }

    /**
     * Normalize input data for Opis validation.
     *
     * @param  mixed  $value
     * @return mixed|null
     */
    protected function normalizeData($value)
    {
        if (is_array($value) || is_object($value)) {
            // Convert to JSON and back to ensure proper object/array structure for Opis
            return json_decode(json_encode($value, JSON_FORCE_OBJECT), false);
        }

        if (! is_string($value)) {
            return $value;
        }

        try {
            return json_decode($value, false, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            $this->errorMessage = "Invalid JSON format: {$e->getMessage()}";

            return null;
        }
    }

    /**
     * Format the validation error message.
     */
    protected function formatValidationError(ValidationError $error): string
    {
        $keyword = $error->keyword();
        $dataPath = implode('.', $error->data()->path() ?? []);

        return  $dataPath !== '' ?
            "Validation failed at '$dataPath': $keyword" :
            "Validation failed: $keyword";
    }
}
