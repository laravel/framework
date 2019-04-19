<?php

namespace Illuminate\Validation\Rules;

class Pwned
{
    /**
     * The number of times the password is allowed to appear.
     *
     * @var int
     */
    protected $threshold;

    /**
     * Ignore the error if the HaveIBeenPwned API fails to respond.
     *
     * @var bool
     */
    protected $skipOnError;

    /**
     * Pwned constructor.
     * @param int $threshold
     * @param bool $skipOnError
     */
    public function __construct(int $threshold = 1, bool $skipOnError = false)
    {
        $this->threshold = $threshold;
        $this->skipOnError = $skipOnError;
    }

    /**
     * @param int $threshold
     * @return Pwned
     */
    public function threshold(int $threshold): Pwned
    {
        $this->threshold = $threshold;

        return $this;
    }

    /**
     * @param bool $skipOnError
     * @return Pwned
     */
    public function skipOnError(bool $skipOnError): Pwned
    {
        $this->skipOnError = $skipOnError;

        return $this;
    }

    public function __toString(): string
    {
        $result = sprintf('pwned:threshold=%d', $this->threshold);

        if ($this->skipOnError) {
            $result .= ',skipOnError';
        }

        return $result;
    }
}
