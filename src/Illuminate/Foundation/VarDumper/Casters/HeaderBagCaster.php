<?php

namespace Illuminate\Foundation\VarDumper\Casters;

use Illuminate\Foundation\VarDumper\Key;

class HeaderBagCaster extends Caster
{
    /**
     * @inheritdoc
     */
    protected function cast($target, $properties, $stub, $isNested, $filter = 0)
    {
        $result = collect($target->all())
            ->map(function (array $headers) {
                return 1 === count($headers)
                    ? $headers[0]
                    : $headers;
            })
            ->mapWithKeys(fn ($value, $key) => [Key::virtual($key) => $value])
            ->all();

        $result[Key::protected('cacheControl')] = $properties[Key::protected('cacheControl')];

        return $result;
    }
}
