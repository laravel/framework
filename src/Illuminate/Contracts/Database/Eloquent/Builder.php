<?php

namespace Illuminate\Contracts\Database\Eloquent;

use Illuminate\Contracts\Database\Query\Builder as BaseContract;

/**
 * This interface is intentionally empty and exists mostly to improve IDE support
 * and provide a simple type-hinting option for those who prefer it.
 *
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
interface Builder extends BaseContract
{

}
