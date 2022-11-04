<?php

namespace Illuminate\Database\Eloquent\Concerns;

use Illuminate\Database\Eloquent\MissingAttributeException;

trait RestrictsAttributes
{
    /**
     * Indicates if an exception should be thrown instead of silently discarding non-fillable attributes.
     *
     * @var bool
     */
    protected static $modelsShouldPreventSilentlyDiscardingAttributes = false;

    /**
     * The callback that is responsible for handling discarded attribute violations.
     *
     * @var callable|null
     */
    protected static $discardedAttributeViolationCallback;

    /**
     * Indicates if an exception should be thrown when trying to access a missing attribute on a retrieved model.
     *
     * @var bool
     */
    protected static $modelsShouldPreventAccessingMissingAttributes = false;

    /**
     * The callback that is responsible for handling missing attribute violations.
     *
     * @var callable|null
     */
    protected static $missingAttributeViolationCallback;

    /**
     * Determine if discarding guarded attribute fills is disabled.
     *
     * @return bool
     */
    public static function preventsSilentlyDiscardingAttributes()
    {
        return static::$modelsShouldPreventSilentlyDiscardingAttributes;
    }

    /**
     * Determine if accessing missing attributes is disabled.
     *
     * @return bool
     */
    public static function preventsAccessingMissingAttributes()
    {
        return static::$modelsShouldPreventAccessingMissingAttributes;
    }

    /**
     * Prevent non-fillable attributes from being silently discarded.
     *
     * @param  bool  $value
     * @return void
     */
    public static function preventSilentlyDiscardingAttributes($value = true)
    {
        static::$modelsShouldPreventSilentlyDiscardingAttributes = $value;
    }

    /**
     * Register a callback that is responsible for handling discarded attribute violations.
     *
     * @param  callable|null  $callback
     * @return void
     */
    public static function handleDiscardedAttributeViolationUsing(?callable $callback)
    {
        static::$discardedAttributeViolationCallback = $callback;
    }

    /**
     * Prevent accessing missing attributes on retrieved models.
     *
     * @param  bool  $value
     * @return void
     */
    public static function preventAccessingMissingAttributes($value = true)
    {
        static::$modelsShouldPreventAccessingMissingAttributes = $value;
    }

    /**
     * Register a callback that is responsible for handling lazy loading violations.
     *
     * @param  callable|null  $callback
     * @return void
     */
    public static function handleMissingAttributeViolationUsing(?callable $callback)
    {
        static::$missingAttributeViolationCallback = $callback;
    }

    /**
     * Either throw a missing attribute exception or return null depending on Eloquent's configuration.
     *
     * @param  string  $key
     * @return null
     *
     * @throws \Illuminate\Database\Eloquent\MissingAttributeException
     */
    protected function throwMissingAttributeExceptionIfApplicable($key)
    {
        if (self::preventsAccessingMissingAttributes() &&
            $this->exists &&
            ! $this->wasRecentlyCreated) {
            if (isset(static::$missingAttributeViolationCallback)) {
                return call_user_func(static::$missingAttributeViolationCallback, $this, $key);
            }

            throw new MissingAttributeException($this, $key);
        }

        return null;
    }
}
