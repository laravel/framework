<?php

namespace Illuminate\Foundation\VarDumper\Casters;

class ResponseCaster extends Caster
{
    /**
     * @inheritdoc
     */
    protected function cast($target, $properties, $stub, $isNested, $filter = 0)
    {
        return $properties
            ->filter()
            ->applyCutsToStub($stub, $properties)
            ->all();
    }
}
