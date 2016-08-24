<?php

namespace Illuminate\Container\Traits;

trait AliasTrait
{
    /**
     * Alias a type to a different name.
     *
     * @param  string  $abstract
     * @param  string  $alias
     * @return void
     */
    public function alias($abstract, $alias)
    {
        $alias = $this->normalize($alias);
        $abstract = $this->normalize($abstract);

        $this->bindings[$alias] = &$this->bindings[$abstract];
    }

}
