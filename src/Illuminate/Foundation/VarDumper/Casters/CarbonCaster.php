<?php

namespace Illuminate\Foundation\VarDumper\Casters;

class CarbonCaster extends Caster
{
    /**
     * @inheritdoc
     */
    protected function cast($target, $properties, $stub, $isNested, $filter = 0)
    {
        return $properties
            ->putVirtual('date', $target->format($this->getFormat($target)))
            ->when($isNested, fn ($properties) => $properties->only('date'))
            ->except(['constructedObjectId', 'dumpProperties'])
            ->filter()
            ->reorder(['date', '*'])
            ->applyCutsToStub($stub, $properties)
            ->all();
    }

    /**
     * Dynamically create the debug format based on what timestamp data exists.
     *
     * @param  \Carbon\CarbonInterface  $target
     * @return string
     */
    protected function getFormat($target): string
    {
        // Only include microseconds if we have it
        $microseconds = '000000' === $target->format('u')
            ? ''
            : '.u';

        // Only include timezone name ("America/New_York") if we have it
        $timezone = $target->getTimezone()->getLocation()
            ? ' e (P)'
            : ' P';

        return 'Y-m-d H:i:s'.$microseconds.$timezone;
    }
}
