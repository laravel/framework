<?php

namespace Illuminate\Http\Resources\JsonApi;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class JsonApiRequest extends Request
{
    /**
     * Get the request's included fields.
     */
    public function sparseFields(string $key): array
    {
        $fieldsets = Arr::get($this->array('fields'), $key, '');

        return empty($fieldsets)
            ? []
            : explode(',', $fieldsets);
    }

    /**
     * Get the request's included relationships.
     */
    public function sparseIncluded(): array
    {
        $included = (string) $this->string('include', '');

        return empty($included)
            ? []
            : explode(',', $included);
    }
}
