<?php

namespace Illuminate\Database\Eloquent;

use Illuminate\Support\BinaryCodec;
use Illuminate\Support\Str;
use InvalidArgumentException;

class ModelKey
{
    public static function cast(Model $model, int|string|null $value = null): int|string|null
    {
        if ($value === null) {
            return null;
        }

        $keyType = $model->getModelKeyType();

        return match ($keyType) {
            ModelKeyType::INT => (int) $value,
            ModelKeyType::STRING => (string) $value,
            ModelKeyType::BINARY => $keyType->isBinary()
                ? BinaryCodec::encode($value, self::resolveBinaryKeyFormat($model))
                : null,
        };
    }

    public static function resolveBinaryKeyFormat(Model $model): string
    {
        if (method_exists($model, 'getBinaryIdFormat')) {
            return $model->getBinaryIdFormat();
        }

        $cast = Str::of(data_get($model->getCasts(), $model->getKeyName()));

        if ($cast->contains(':')) {
            return $cast->after(':')->before(',')->value();
        }

        throw new InvalidArgumentException(sprintf(
            'Unable to resolve binary key format. Model [%s] contains invalid binary format [%s] for key [%s]',
            $model::class,
            $cast->value(),
            $model->getKeyName(),
        ));
    }
}
