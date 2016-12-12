<?php

namespace Illuminate\Database\Eloquent\Casters;

class TimestampCaster extends AbstractCaster
{
    /**
     * {@inheritdoc}
     */
    public function as($value)
    {
        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function from($value)
    {
        return (new DateTimeCaster())->options($this->options)
                                   ->as($value)
                                   ->getTimestamp();
    }
}
