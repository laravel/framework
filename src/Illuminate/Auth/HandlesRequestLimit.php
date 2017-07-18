<?php

namespace Illuminate\Auth;

/**
 * @property  int|float quota
 */
trait HandlesRequestLimit
{
    /**
     * Set the user request limit.
     *
     * @param  float|int $quota
     * @return $this
     */
    public function setRequestLimit($quota)
    {
        $this->quota = (int) $quota;

        return $this;
    }

    /**
     * Get the user request request limit.
     *
     * @return int
     */
    public function getRequestLimit()
    {
        return $this->quota ?? 60;
    }
}
