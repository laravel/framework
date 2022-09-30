<?php

namespace Illuminate\Foundation\VarDumper\Casters;

use Illuminate\Foundation\VarDumper\Properties;

abstract class Caster
{
    /**
     * Whether this caster is enabled.
     *
     * @var bool
     */
    protected static $enabled = true;

    /**
     * Cast the target for dumping.
     *
     * @param  mixed  $target
     * @param  \Illuminate\Foundation\VarDumper\Properties  $properties
     * @param  \Symfony\Component\VarDumper\Cloner\Stub  $stub
     * @param  bool  $isNested
     * @param  int  $filter
     * @return array
     */
    abstract protected function cast($target, $properties, $stub, $isNested, $filter = 0);

    /**
     * Invoke the caster.
     *
     * @param  mixed  $target
     * @param  array  $properties
     * @param  \Symfony\Component\VarDumper\Cloner\Stub  $stub
     * @param  bool  $isNested
     * @param  int  $filter
     * @return array
     */
    public function __invoke($target, $properties, $stub, $isNested, $filter = 0)
    {
        return self::$enabled
            ? $this->cast($target, new Properties($properties), $stub, $isNested, $filter)
            : $properties;
    }

    /**
     * Disable this caster.
     *
     * @return void
     */
    public static function disable()
    {
        self::$enabled = false;
    }

    /**
     * Enable this caster.
     *
     * @return void
     */
    public static function enable()
    {
        self::$enabled = true;
    }
}
