<?php

namespace Illuminate\Support;

use Carbon\Carbon as BaseCarbon;
use Cake\Chronos\MutableDateTime;

class Carbon extends BaseCarbon
{
    /**
     * Return an immutable datetime.
     *
     * @return \Cake\Chronos\Chronos
     */
    public function immutable()
    {
        return MutableDateTime::createFromTimestamp(
            $this->getTimestamp(),
            $this->getTimezone()
        )->toImmutable();
    }
}
