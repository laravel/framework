<?php

namespace Illuminate\Support;

use Illuminate\Foundation\Auth\User;
use WeakMap;

class Once
{
    /**
     * The current globally used instance.
     *
     * @var static|null
     */
    protected static $instance = null;

    /**
     * Indicates if the once instance is enabled.
     *
     * @var bool
     */
    protected static $enabled = true;

    /**
     * Create a new once instance.
     *
     * @param  \WeakMap<object, Onceable>  $values
     * @param  bool  $enabled
     * @return void
     */
    protected function __construct(protected WeakMap $values)
    {
        //
    }

    /**
     * Create a new once instance.
     *
     * @return static
     */
    public static function instance(): static
    {
        return static::$instance ??= new static(new WeakMap);
    }

    /**
     * Flush the once instance.
     *
     * @return void
     */
    public static function flush()
    {
        static::$instance = null;
    }

    /**
     * Disable the once instance.
     *
     * @return void
     */
    public static function disable()
    {
        static::$enabled = false;
    }

    /**
     * Re-enable the once instance, if it was disabled.
     *
     * @return void
     */
    public static function enable()
    {
        static::$enabled = true;
    }

    /**
     * Get the value of the given onceable.
     *
     * @param  Onceable  $onceable
     * @return mixed
     */
    public function value(Onceable $onceable)
    {
        if (! static::$enabled) {
            return value($onceable->callable);
        }

        $object = $onceable->object ?: $this;
        $hash = $onceable->hash;

        if (isset($this->values[$object][$hash])) {
            return $this->values[$object][$hash];
        }

        if (! isset($this->values[$object])) {
            $this->values[$object] = [];
        }

        return $this->values[$object][$hash] = value($onceable->callable);
    }
}
