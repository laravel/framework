<?php

namespace Illuminate\Database\Schema\Columns;

/**
 * Class Timestamp
 *
 * @property-read bool $useCurrent
 */
class Timestamp extends Time
{
    /**
     * @var bool
     */
    protected $useCurrent = false;

    /**
     * Set TIMESTAMP columns to use CURRENT_TIMESTAMP as default value
     *
     * @return Timestamp
     */
    public function useCurrent(): Timestamp
    {
        $this->useCurrent = true;
        return $this;
    }
}
