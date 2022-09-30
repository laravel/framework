<?php

namespace Illuminate\Foundation\VarDumper\Casters;

use Illuminate\Foundation\VarDumper\Key;

class DatabaseConnectionCaster extends Caster
{
    /**
     * @inheritdoc
     */
    protected function cast($target, $properties, $stub, $isNested, $filter = 0)
    {
        if (! is_array($config = $properties->getProtected('config'))) {
            return $properties->all();
        }

        $stub->cut += count($properties);

        return [
            Key::virtual('name')     => $config['name'],
            Key::virtual('database') => $config['database'],
            Key::virtual('driver')   => $config['driver'],
        ];
    }
}
