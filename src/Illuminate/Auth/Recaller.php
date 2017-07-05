<?php

namespace Illuminate\Auth;

use Illuminate\Support\Str;

class Recaller
{
    /**
     * The "recaller" / "remember me" cookie string.
     *
     * @var string
     */
    protected $recaller;

    /**
     * Create a new recaller instance.
     *
     * @param  string  $recaller
     * @return void
     */
    public function __construct($recaller)
    {
        $this->recaller = $recaller;
    }

    /**
     * Get the user ID from the recaller.
     *
     * @return string
     */
    public function id()
    {
        return explode('|', $this->recaller, 3)[0];
    }

    /**
     * Get the "remember token" token from the recaller.
     *
     * @return string
     */
    public function token()
    {
        return explode('|', $this->recaller, 3)[1];
    }

    /**
     * Get the password from the recaller.
     *
     * @return string
     */
    public function hash()
    {
        return explode('|', $this->recaller, 3)[2];
    }

    /**
     * Determine if the recaller is valid.
     *
     * @return bool
     */
    public function valid()
    {
        return $this->properString() && $this->hasAllSegments();
    }

    /**
     * Determine if the recaller is an invalid string.
     *
     * @return bool
     */
    protected function properString()
    {
        return is_string($this->recaller) && Str::contains($this->recaller, '|');
    }

    /**
     * Determine if the recaller has all segments.
     *
     * @return bool
     */
    protected function hasAllSegments()
    {
        $segments = explode('|', $this->recaller);

        return count($segments) == 3 && trim($segments[0]) !== '' && trim($segments[1]) !== '';
    }
}
