<?php

namespace Illuminate\Console\Scheduling\Sequences;

use InvalidArgumentException;

class DefinitionRegistry
{
    public function resolve(string $name): SequenceDefinition
    {
        $class = config("scheduling.definitions.{$name}");

        if (! is_string($class) || ! is_subclass_of($class, SequenceDefinition::class)) {
            throw new InvalidArgumentException("Unknown sequence definition: {$name}");
        }

        return app($class);
    }
}
