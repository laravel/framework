<?php

namespace Illuminate\Support\EnvProcessors;

use JsonException;
use RuntimeException;

final class JsonProcessor implements EnvProcessorInterface
{
    /**
     * JSON decodes the given env variable value.
     *
     * @param  string  $value
     * @return array|null
     *
     * @throws RuntimeException
     * @throws JsonException
     */
    public function __invoke(string $value): ?array
    {
        $decodedValue = json_decode($value, true, 512, JSON_THROW_ON_ERROR);

        if (null !== $decodedValue && ! is_array($decodedValue)) {
            throw new RuntimeException(sprintf('Invalid JSON env var: array or null expected, "%s" given.', get_debug_type($decodedValue)));
        }

        return $decodedValue;
    }
}
