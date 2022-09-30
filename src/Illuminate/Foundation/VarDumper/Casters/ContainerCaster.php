<?php

namespace Illuminate\Foundation\VarDumper\Casters;

class ContainerCaster extends Caster
{
    /**
     * @inheritdoc
     */
    protected function cast($target, $properties, $stub, $isNested, $filter = 0)
    {
        if ($isNested) {
            $stub->cut += $properties->count();

            return [];
        }

        $keep = [
            'bindings',
            'aliases',
            'resolved',
            'extenders',
        ];

        return $properties
            ->only($keep)
            ->reorder($keep)
            ->applyCutsToStub($stub, $properties)
            ->all();
    }
}
