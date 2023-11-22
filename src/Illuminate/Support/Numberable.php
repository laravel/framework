<?php

namespace Illuminate\Support;

use Closure;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Traits\Conditionable;
use Illuminate\Support\Traits\Macroable;
use Illuminate\Support\Traits\Tappable;
use Symfony\Component\VarDumper\VarDumper;

class Numberable
{
    use Conditionable, Macroable, Tappable;

    /**
     * The underlying numeric value.
     *
     * @var int|float
     */
    protected $value;

    /**
     * Create a new instance of the class.
     *
     * @param  int|float  $value
     * @return void
     */
    public function __construct($value)
    {
        $this->value = $value;
    }

    /**
     * Format the given number according to the current locale.
     *
     * @param  int|null  $precision
     * @param  int|null  $maxPrecision
     * @param  ?string  $locale
     * @return string|false
     */
    public function format(?int $precision = null, ?int $maxPrecision = null, ?string $locale = null)
    {
        return Number::format($this->value, $precision, $maxPrecision, $locale);
    }

    /**
     * Spell out the given number in the given locale.
     *
     * @param  ?string  $locale
     * @return string
     */
    public function spell(?string $locale = null)
    {
        return Number::spell($this->value, $locale);
    }

    /**
     * Convert the given number to ordinal form.
     *
     * @param  ?string  $locale
     * @return string
     */
    public function ordinal(?string $locale = null)
    {
        return Number::ordinal($this->value, $locale);
    }

    /**
     * Convert the given number to its percentage equivalent.
     *
     * @param  int  $precision
     * @param  int|null  $maxPrecision
     * @param  ?string  $locale
     * @return string|false
     */
    public function percentage(int $precision = 0, ?int $maxPrecision = null, ?string $locale = null)
    {
        return Number::percentage($this->value, $precision, $maxPrecision, $locale);
    }

    /**
     * Convert the given number to its currency equivalent.
     *
     * @param  string  $in
     * @param  ?string  $locale
     * @return string|false
     */
    public function currency(string $in = 'USD', ?string $locale = null)
    {
        return Number::currency($this->value, $in, $locale);
    }

    /**
     * Convert the given number to its file size equivalent.
     *
     * @param  int  $precision
     * @param  int|null  $maxPrecision
     * @return string
     */
    public function fileSize(int $precision = 0, ?int $maxPrecision = null)
    {
        return Number::fileSize($this->value, $precision, $maxPrecision);
    }

    /**
     * Convert the number to its human readable equivalent.
     *
     * @param  int  $precision
     * @param  int|null  $maxPrecision
     * @return string
     */
    public function forHumans(int $precision = 0, ?int $maxPrecision = null)
    {
        return Number::forHumans($this->value, $precision, $maxPrecision);
    }

    /**
     * Execute the given callback using the given locale.
     *
     * @param  string  $locale
     * @param  callable  $callback
     * @return mixed
     */
    public function withLocale(string $locale, callable $callback)
    {
        return Number::withLocale($locale, $callback);
    }

    /**
     * Set the default locale.
     *
     * @param  string  $locale
     * @return void
     */
    public function useLocale(string $locale)
    {
        return Number::useLocale($locale);
    }

    /**
     * Dump the raw value.
     *
     * @return int|float
     */
    public function dump()
    {
        VarDumper::dump($this->value);

        return $this;
    }

    /**
     * Dump the raw value and end the script.
     *
     * @return never
     */
    public function dd()
    {
        $this->dump();

        exit(1);
    }

    /**
     * Get the raw value.
     *
     * @return int|float
     */
    public function rawValue()
    {
        return $this->value;
    }
}
