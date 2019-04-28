<?php

namespace Illuminate\Database\Eloquent\Concerns;

trait HasDebugMethods
{
    /**
     * Debug the current query builder instance.
     *
     * @return void
     */
    public function dd()
    {
        dd($this->toSql(), $this->getBindings());
    }
}
