<?php

namespace Illuminate\Database\Eloquent\Casts;

use Illuminate\Contracts\Database\Eloquent\Castable;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Database\Query\Expression as ExpressionContract;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Query\Grammars\MariaDbGrammar;
use Illuminate\Support\Str;
use InvalidArgumentException;

class AsVector implements Castable
{
    /**
     * Get the caster class to use when casting from / to this cast target.
     *
     * @param  array  $arguments
     * @return \Illuminate\Contracts\Database\Eloquent\CastsAttributes<array<int, float>, \Illuminate\Contracts\Support\Arrayable|array<int, float>>
     */
    public static function castUsing(array $arguments)
    {
        return new class implements CastsAttributes
        {
            public function get($model, $key, $value, $attributes)
            {
                if ($value === null) {
                    return null;
                }

                // A vector assigned to the model but not yet persisted on MariaDB
                // is held as a "vec_fromtext('[...]')" expression, so pull the
                // JSON back out of it...
                if ($value instanceof ExpressionContract) {
                    $value = Str::between($value->getValue($model->getConnection()->getQueryGrammar()), "('", "')");
                }

                // Postgres (pgvector) and MariaDB's vec_totext() return JSON text...
                if (str_starts_with($value, '[')) {
                    return array_map(floatval(...), json_decode($value, true, flags: JSON_THROW_ON_ERROR));
                }

                // ...while MariaDB vector columns return little-endian float32 bytes.
                return array_values(unpack('g*', $value));
            }

            public function set($model, $key, $value, $attributes)
            {
                if ($value === null) {
                    return [$key => null];
                }

                if ($value instanceof Arrayable) {
                    $value = $value->toArray();
                }

                if (! is_array($value)) {
                    throw new InvalidArgumentException(
                        sprintf('The [%s] attribute must be an array of floats or an Arrayable instance.', $key)
                    );
                }

                $vector = json_encode(array_values(array_map(floatval(...), $value)), JSON_THROW_ON_ERROR);

                // MariaDB will not accept text (or raw bytes) bound directly to a
                // vector column, so the JSON has to be converted server-side. The
                // JSON encoding of a float array only ever contains digits, ".",
                // "-", "e", "," and brackets, so it is safe to inline.
                return [
                    $key => $model->getConnection()->getQueryGrammar() instanceof MariaDbGrammar
                        ? new Expression("vec_fromtext('{$vector}')")
                        : $vector,
                ];
            }
        };
    }
}
