<?php

namespace Illuminate\Auth\Access;

class Admission
{
    /**
     * The reason admission was granted.
     *
     * @var string|null
     */
    protected $reason;

    /**
     * Create an admission instance.
     *
     * @param string|null  $reason
     */
    public function __construct($reason = null)
    {
        $this->reason = $reason;
    }

    /**
     * Get the reason for admission.
     *
     * @return string
     */
    public function reason()
    {
        return $this->reason;
    }
}
