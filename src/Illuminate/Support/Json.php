<?php

namespace Illuminate\Support;

use JsonException;
use stdClass;

class Json
{
    /**
     * Encode the given value as JSON.
     *
     * @throws JsonException
     */
    public static function encode(mixed $value, int $flags = 0): string
    {
        return json_encode($value, JSON_THROW_ON_ERROR | $flags);
    }

    /**
     * Decode the given JSON string into an array.
     *
     * @return array<array-key, mixed>
     *
     * @throws JsonException
     */
    public static function decodeToArray(string $json, int $flags = 0): array
    {
        $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR | $flags);

        if (! is_array($decoded)) {
            throw new JsonException('Expected a JSON object or array.');
        }

        return $decoded;
    }

    /**
     * Decode the given JSON string into an object.
     *
     * @throws JsonException
     */
    public static function decodeToObject(string $json, int $flags = 0): stdClass
    {
        $decoded = json_decode($json, false, 512, JSON_THROW_ON_ERROR | $flags);

        if (! $decoded instanceof stdClass) {
            throw new JsonException('Expected a JSON object.');
        }

        return $decoded;
    }
}
