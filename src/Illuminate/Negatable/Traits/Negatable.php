<?php

namespace Illuminate\Negatable\Traits;

use Illuminate\Negatable\HigherOrderNotProxy;

/**
 * @template TModel of \Illuminate\Database\Eloquent\Model
 *
 * @mixin TModel
 */
trait Negatable
{
    /**
     * @return HigherOrderNotProxy<static>
     */
    public function not(): HigherOrderNotProxy
    {
        return new HigherOrderNotProxy($this);
    }
}
