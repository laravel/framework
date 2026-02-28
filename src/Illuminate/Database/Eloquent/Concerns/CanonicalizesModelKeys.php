<?php

namespace Illuminate\Database\Eloquent\Concerns;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\BinaryCodec;

trait CanonicalizesModelKeys
{
    protected function prepareModelKey(mixed $id, Model $model): null|string|int|array
    {
        if ($id instanceof Model) {
            $id = $id->getKey();
        }

        if (is_array($id) || $id instanceof Arrayable) {
            return collect($id)->map(fn (mixed $id) => $this->castModelId($id, $model))->all();
        }

        return $this->castModelId($id, $model);
    }

    private function castModelId(mixed $id, Model $model): null|string|int
    {
        if ($id === null) {
            return null;
        }

        return match (true) {
            $model->getKeyType() === 'string' => (string) $id,
            'binary' && method_exists($model, 'getBinaryIdFormat') => BinaryCodec::encode($id, $model->getBinaryIdFormat()),
            in_array($model->getKeyType(), ['int', 'integer'], true) => (int) $id,
            default => $id,
        };
    }
}
