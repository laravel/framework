<?php

namespace Illuminate\Foundation\VarDumper\Casters;

use Illuminate\Foundation\VarDumper\Key;

class ModelCaster extends Caster
{
    /**
     * @inheritdoc
     */
    protected function cast($target, $properties, $stub, $isNested, $filter = 0)
    {
        $keep = [
            'attributes',
            'exists',
            'wasRecentlyCreated',
            Key::protected('relations'),
        ];

        // If we're dumping the model directly, include a little more data
        if (! $isNested) {
            $keep = array_merge($keep, [
                Key::protected('connection'),
                Key::protected('table'),
                Key::protected('original'),
                Key::protected('changes'),
            ]);
        }

        return $properties
            ->only($keep)
            ->reorder($keep)
            ->applyCutsToStub($stub, $properties)
            ->all();
    }
}
