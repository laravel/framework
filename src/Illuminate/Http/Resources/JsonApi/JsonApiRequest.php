<?php

namespace Illuminate\Http\Resources\JsonApi;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class JsonApiRequest extends Request
{
    public function sparseIncluded(): array
    {
        $included = (string) $this->string('include', '');

        if (empty($included)) {
            return [];
        }

        return explode(',', $included);
    }

    public function sparseFields(string $key): array
    {
        $fieldsets = Arr::get($this->array('fields'), $key, '');

        if (empty($fieldsets)) {
            return [];
        }

        return explode(',', $fieldsets);
    }
}
