<?php

namespace Illuminate\Foundation\Http\Middleware;

class TrimStrings extends TransformsRequest
{
    /**
     * The exact attributes that should not be trimmed.
     *
     * @var array
     */
    protected $except = [
        //
    ];

    /**
     * The regular expressions matching attributes that should not be trimmed.
     *
     * @var array
     */
    protected $exceptPattern = [
        //
    ];

    /**
     * The regular expression generated from the exceptPattern.
     *
     * @var string
     */
    protected $patternString;

    public function __construct()
    {
        $this->generatePatternString();
    }

    /**
     * Generate the regular expression pattern from the exceptPattern.
     *
     * @return void
     */
    protected function generatePatternString()
    {
        if (count($this->exceptPattern) > 0) {
            $this->patternString = sprintf('/(%s)/', implode('|', $this->exceptPattern));
        }
    }

    /**
     * Transform the given value.
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return mixed
     */
    protected function transform($key, $value)
    {
        if ($this->matchesExact($key) || $this->matchesPattern($key)) {
            return $value;
        }

        return is_string($value) ? trim($value) : $value;
    }

    /**
     * Validate if the key is in the except array.
     *
     * @param string $key
     *
     * @return bool
     */
    protected function matchesExact($key)
    {
        return in_array($key, $this->except, true);
    }

    /**
     * Validate if the key matches the except regular expression.
     *
     * @param string $key
     *
     * @return bool
     */
    protected function matchesPattern($key)
    {
        if (!$this->patternString) {
            return false;
        }

        return preg_match($this->patternString, $key) !== 0;
    }
}
