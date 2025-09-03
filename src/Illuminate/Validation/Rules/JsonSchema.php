<?php

namespace Illuminate\Validation\Rules;

use Exception;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\JsonSchema\JsonSchema as Schema;
use Opis\JsonSchema\Errors\ValidationError;
use Opis\JsonSchema\Validator;

class JsonSchema implements Rule
{
    /**
     * The JSON schema instance.
     */
    protected Schema $schema;

    /**
     * The validation error message.
     */
    protected ?string $errorMessage = null;

    /**
     * Create a new JSON schema validation rule.
     */
    public function __construct(Schema $schema)
    {
        $this->schema = $schema;
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

        if ($data === null && $this->errorMessage) {
            return false;
        }

        try {
            $validator = new Validator;
            $schemaString = $this->schema->toString();
            $result = $validator->validate($data, $schemaString);

            if (! $result->isValid()) {
                $this->errorMessage = $this->formatValidationError($result->error());

                return false;
            }

            return true;
        } catch (Exception $e) {
            $this->errorMessage = "Schema validation error: {$e->getMessage()}";

            return false;
        }
    }

    /**
     * Normalize input data for Opis validation.
     *
     * @param  mixed  $value
     * @return mixed|null
     */
    protected function normalizeData($value)
    {
        if (is_string($value)) {
            $decoded = json_decode($value);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->errorMessage = 'Invalid JSON format: '.json_last_error_msg();

                return null;
            }

            return $decoded;
        }

        if (is_array($value) || is_object($value)) {
            // Convert to JSON and back to ensure proper object/array structure for Opis
            return json_decode(json_encode($value, JSON_FORCE_OBJECT), false);
        }

        return $value;
    }

    /**
     * Format the validation error message.
     */
    protected function formatValidationError(?ValidationError $error): string
    {
        $keyword = $error->keyword();
        $dataPath = implode('.', $error->data()->path() ?? []);

        if ($dataPath) {
            return "Validation failed at '{$dataPath}': {$keyword}";
        }

        return "Validation failed: {$keyword}";
    }

    /**
     * Get the validation error message.
     */
    public function message(): string
    {
        return $this->errorMessage ?? 'The :attribute does not match the required schema.';
    }
}
