<?php

namespace Illuminate\Support\Hooks;

use Closure;
use Illuminate\Contracts\Support\Hook as HookContract;

class TraitHook implements HookContract
{
    /**
     * Constructor
     *
     * @param  string  $prefix
     * @return void
     */
    public function __construct(public string $prefix)
    {
        //
    }

    /**
     * @inheritdoc
     */
    public function run($instance = null, array $arguments = [])
    {
        foreach (class_uses_recursive($instance) as $trait) {
            $method = $this->prefix.class_basename($trait);

            if (method_exists($instance, $method)) {
                if (is_object($instance)) {
                    $instance->$method(...$arguments);
                } else {
                    forward_static_call_array([$instance, $method], $arguments);
                }
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function cleanup($instance, array $arguments = [])
    {
        // No cleanup is necessary
    }
}
