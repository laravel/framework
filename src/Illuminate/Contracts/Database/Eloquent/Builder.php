<?php

namespace Illuminate\Contracts\Database\Eloquent;

use Illuminate\Contracts\Database\Query\Builder as BaseContract;

/**
 * This interface is intentionally empty and exists to improve IDE support.
 * @template TResult
 * @mixin \Illuminate\Database\Eloquent\Builder<TResult>
 */
interface Builder extends BaseContract
{
}
