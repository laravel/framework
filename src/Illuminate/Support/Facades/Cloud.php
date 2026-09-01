<?php

namespace Illuminate\Support\Facades;

use Illuminate\Foundation\Cloud\CloudManager;

/**
 * @method static bool hosted()
 * @method static bool usesManagedQueues()
 * @method static \Illuminate\Foundation\Cloud\Queue queue()
 * @method static void macro(string $name, object|callable $macro)
 * @method static void mixin(object $mixin, bool $replace = true)
 * @method static bool hasMacro(string $name)
 * @method static void flushMacros()
 *
 * @see \Illuminate\Foundation\Cloud\CloudManager
 */
class Cloud extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return CloudManager::class;
    }
}
