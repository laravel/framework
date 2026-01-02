<?php

namespace Illuminate\Database\Eloquent\Casts;

use Illuminate\Contracts\Database\Eloquent\Castable;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

class AsJsonPath implements Castable
{
    /**
     * Get the caster class to use when casting from / to this cast target.
     *
     * @return \Illuminate\Contracts\Database\Eloquent\CastsAttributes<mixed, mixed>
     */
    public static function castUsing(array $arguments)
    {
        return new class($arguments) implements CastsAttributes
        {
            public function __construct(protected array $arguments) {}

            public function get($model, $key, $value, $attributes)
            {
                $targetColumn = $this->arguments[0] ?? null;
                $jsonPath = $this->arguments[1] ?? null;
                $type = $this->arguments[2] ?? null;

                if (! $targetColumn || ! $jsonPath) {
                    return null;
                }

                if (array_key_exists($key, $attributes)) {
                    if ($type) {
                        return $this->castValue($model, $key, $attributes[$key], $type);
                    }

                    return $attributes[$key];
                }

                if (! isset($attributes[$targetColumn])) {
                    return null;
                }

                $rootValue = $attributes[$targetColumn];

                if ($model->hasCast($targetColumn, ['encrypted', 'encrypted:array', 'encrypted:collection', 'encrypted:json', 'encrypted:object'])) {
                    $rootValue = $model->fromEncryptedString($rootValue);
                }

                $decoded = is_array($rootValue) ? $rootValue : $model->fromJson($rootValue);

                if (! is_array($decoded)) {
                    return null;
                }

                $extractedValue = data_get($decoded, $jsonPath);

                if ($type && $extractedValue !== null) {
                    return $this->castValue($model, $key, $extractedValue, $type);
                }

                return $extractedValue;
            }

            public function set($model, $key, $value, $attributes)
            {
                $targetColumn = $this->arguments[0] ?? null;
                $jsonPath = $this->arguments[1] ?? null;

                if (! $targetColumn || ! $jsonPath) {
                    return [];
                }

                if (array_key_exists($key, $attributes)) {
                    return [$key => $value];
                }

                $path = str_replace('.', '->', $jsonPath);

                return $model->fillJsonAttribute($targetColumn.'->'.$path, $value)->getAttributes();
            }

            protected function castValue($model, $key, $value, $type)
            {
                $castType = strtolower($type);

                switch ($castType) {
                    case 'int':
                    case 'integer':
                        return (int) $value;
                    case 'real':
                    case 'float':
                    case 'double':
                        return $model->fromFloat($value);
                    case 'string':
                        return (string) $value;
                    case 'bool':
                    case 'boolean':
                        return (bool) $value;
                    case 'array':
                    case 'json':
                        return is_array($value) ? $value : $model->fromJson($value);
                    case 'object':
                        return is_object($value) ? $value : $model->fromJson($value, true);
                    case 'collection':
                        return new \Illuminate\Support\Collection(is_array($value) ? $value : $model->fromJson($value));
                    case 'date':
                        return $model->asDate($value);
                    case 'datetime':
                        return $model->asDateTime($value);
                    case 'timestamp':
                        return $model->asTimestamp($value);
                    default:
                        if (str_starts_with($castType, 'decimal:')) {
                            $decimals = (int) explode(':', $type, 2)[1];

                            return $model->asDecimal($value, $decimals);
                        }

                        return $value;
                }
            }
        };
    }

    /**
     * Specify the target column, JSON path, and optional type for the cast.
     *
     * @param  string  $targetColumn
     * @param  string  $jsonPath
     * @param  string|null  $type
     * @return string
     */
    public static function using($targetColumn, $jsonPath, $type = null): string
    {
        $arguments = [$targetColumn, $jsonPath];

        if ($type !== null) {
            $arguments[] = $type;
        }

        return static::class.':'.implode(',', $arguments);
    }
}
