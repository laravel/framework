<?php

namespace Illuminate\Foundation\VarDumper\Casters;

class RequestCaster extends Caster
{
    /**
     * @inheritdoc
     */
    protected function cast($target, $properties, $stub, $isNested, $filter = 0)
    {
        return $properties
            ->except(['userResolver', 'routeResolver'])
            ->filter()
            ->applyCutsToStub($stub, $properties)
            ->all();
    }
}
