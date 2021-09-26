<?php

namespace Illuminate\Support\EnvProcessors;

final class Base64Processor implements EnvProcessorInterface
{
    /**
     * Base64 URL decodes the given env variable value.
     *
     * @param  string  $value
     * @return string
     */
    public function __invoke(string $value): string
    {
        return base64_decode(strtr($value, '-_', '+/'));
    }
}
