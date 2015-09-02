<?php

namespace Illuminate\Auth\Access;

class Admission
{
    /**
     * Indicates whether admission was allowed.
     *
     * @var bool
     */
    protected $allowed;

    /**
     * The reason admission was allowed/denied.
     *
     * @var string|null
     */
    protected $reason;

    /**
     * Create a new admission instance.
     *
     * @return void
     */
    protected function __construct()
    {
        //
    }

    /**
     * Create an allowed admission.
     *
     * @param  string|null  $reason
     * @return static
     */
    public static function allow($reason = null)
    {
        $admission = new static;

        $admission->allowed = true;
        $admission->reason = $reason;

        return $admission;
    }

    /**
     * Create a denied admission.
     *
     * @param  string  $reason
     * @return static
     */
    public static function deny($reason = 'This action is unauthorized.')
    {
        $admission = new static;

        $admission->allowed = false;
        $admission->reason = $reason;

        return $admission;
    }

    /**
     * Create a new admission from the given value.
     *
     * @param  mixed  $value
     * @return static
     */
    public static function fromValue($value)
    {
        if ($value instanceof static) {
            return $value;
        }

        return $value ? static::allow() : static::deny();
    }

    /**
     * Gets the reason the admission was allowed/denied.
     *
     * @return string
     */
    public function reason()
    {
        return $this->reason;
    }

    /**
     * Checks whether the admission was allowed.
     *
     * @return bool
     */
    public function allowed()
    {
        return $this->allowed;
    }

    /**
     * Checks whether the admission was denied.
     *
     * @return bool
     */
    public function denied()
    {
        return ! $this->allowed;
    }
}
