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
        return MutableDateTime::createFromFormat(
            'Y-m-d H:i:s',
            $this->toDateTimeString(),
            $this->getTimezone()
        )->toImmutable();
    }
}
