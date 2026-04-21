<?php

namespace Illuminate\Foundation\Http;

use RuntimeException;

class UnknownFieldsException extends RuntimeException
{
    /**
     * The class name of the form request.
     */
    public string $formRequest;

    /**
     * The unknown fields that were sent.
     *
     * @var array<int, string>
     */
    public array $fields;

    /**
     * Create a new exception instance.
     *
     * @param  array<int, string>  $fields
     */
    public function __construct(FormRequest $formRequest, array $fields)
    {
        $class = get_class($formRequest);

        parent::__construct('Unknown fields ['.implode(', ', $fields)."] on form request [{$class}].");

        $this->formRequest = $class;
        $this->fields = $fields;
    }
}
