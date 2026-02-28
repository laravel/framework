<?php

namespace Illuminate\Database\Eloquent\Concerns;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelKey;
use Illuminate\Support\Collection;

trait CanonicalizesModelKeys
{
    protected function prepareModelKey(Model $model, mixed $id): null|string|int|array
    {
        if ($id instanceof Model) {
            $id = $id->getKey();
        }

        if (is_array($id) || $id instanceof Arrayable) {
            return Collection::make($id)
                ->map(fn (string|int $value) => ModelKey::cast($model, $value))
                ->all();
        }

        return ModelKey::cast($model, $id);
    }
}
