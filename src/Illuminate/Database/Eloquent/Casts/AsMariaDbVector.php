<?php

namespace Illuminate\Database\Eloquent\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Expression;
use InvalidArgumentException;

class AsMariaDbVector implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes)
    {
        if ($value === null) {
            return null;
        }

        // JSON string format from VEC_ToText() — e.g. "[0.1, 0.2, 0.3]"
        if (is_string($value) && str_starts_with($value, '[')) {
            $decoded = json_decode($value, true);

            if ($decoded === null) {
                throw new InvalidArgumentException(
                    "Malformed JSON vector string for key '{$key}': {$value}"
                );
            }

            return array_map('floatval', $decoded);
        }

        // Binary format: native MariaDB VECTOR storage (32-bit IEEE 754 little-endian floats).
        // 'g*' forces little-endian interpretation regardless of host byte order.
        if (is_string($value) && strlen($value) > 0) {
            $unpacked = unpack('g*', $value);

            return array_values($unpacked);
        }

        return null;
    }

    public function set(Model $model, string $key, mixed $value, array $attributes)
    {
        if ($value === null) {
            return null;
        }

        if (is_array($value) && count($value) === 0) {
            return null;
        }

        // Pass 1: reject non-numeric values before floatval() silently coerces them to 0.0
        foreach ($value as $v) {
            if (! is_numeric($v)) {
                throw new InvalidArgumentException(
                    "Vector values must be numeric; non-numeric value encountered: " . print_r($v, true)
                );
            }
        }

        $floats = array_map('floatval', $value);

        // Pass 2: reject NAN and INF, which pass is_numeric() but are not valid VECTOR values
        foreach ($floats as $v) {
            if (! is_finite($v)) {
                throw new InvalidArgumentException('Vector values must be finite floats; NAN and INF are not supported.');
            }
        }

        $vectorString = '[' . implode(', ', $floats) . ']';
        return new Expression("vec_fromtext('.$vectorString.')");
    }

}
