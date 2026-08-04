<?php

namespace Illuminate\Foundation\ConsoleDumps;

use Symfony\Component\VarDumper\Cloner\Stub;
use Symfony\Component\VarDumper\Cloner\VarCloner;

class DumpCloner extends VarCloner
{
    /**
     * Clone a variable into data that can be safely sent to the dump server.
     */
    protected function doClone(mixed $var): array
    {
        return $this->normalizeStubs(parent::doClone($var));
    }

    /**
     * Normalize specialized stubs to the base stub supported by the server protocol.
     */
    protected function normalizeStubs(array $values): array
    {
        foreach ($values as $key => $value) {
            if ($value instanceof Stub && $value::class !== Stub::class) {
                $stub = new Stub;
                $stub->type = $value->type;
                $stub->class = $value->class;
                $stub->value = $value->value;
                $stub->cut = $value->cut;
                $stub->handle = $value->handle;
                $stub->refCount = $value->refCount;
                $stub->position = $value->position;
                $stub->attr = $value->attr;

                $values[$key] = $stub;
            } elseif (is_array($value)) {
                $values[$key] = $this->normalizeStubs($value);
            }
        }

        return $values;
    }
}
