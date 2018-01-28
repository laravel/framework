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
     * @return Timestamp
     */
    public function useCurrent(): Timestamp
    {
        $this->useCurrent = true;
        return $this;
    }
}
