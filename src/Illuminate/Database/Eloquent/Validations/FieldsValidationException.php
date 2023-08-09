<?php

namespace Illuminate\Database\Eloquent\Validations;

use Attribute;
use Illuminate\Support\Arr;
use InvalidArgumentException;
use Throwable;

class FieldsValidationException extends \Exception
{
    readonly public array $errors;

    public function __construct(array  $errors, int $code = 0, ?Throwable $previous = null)
    {
        $this->errors = $errors;
        parent::__construct("Field validation errors", $code, $previous);
    }

    public function messages(): array
    {
        return $this->errors;
    }

}
