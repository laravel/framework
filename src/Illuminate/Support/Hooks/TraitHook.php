<?php

namespace Illuminate\Support\Hooks;

use Illuminate\Contracts\Support\Hook as HookContract;

class TraitHook implements HookContract
{
    /**
     * Constructor.
     *
     * @param  string  $prefix
     * @param  int  $priority
     * @return void
     */
    public function __construct(
        public string $prefix,
        public int $priority = HookContract::PRIORITY_NORMAL
    ) {
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

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return $this->prefix;
    }

    /**
     * @inheritDoc
     */
    public function getPriority()
    {
        return $this->priority;
    }
}
