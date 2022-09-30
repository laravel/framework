<?php

namespace Illuminate\Foundation\VarDumper\Casters;

use Illuminate\Foundation\VarDumper\Key;

class ParameterBagCaster extends Caster
{
    /**
     * @inheritdoc
     */
    protected function cast($target, $properties, $stub, $isNested, $filter = 0)
    {
        return collect($target->all())
            ->mapWithKeys(fn ($value, $key) => [Key::virtual($key) => $value])
            ->all();
    }
}
