<?php

namespace Illuminate\Support;

use Illuminate\Support\Traits\Macroable;
use NumberFormatter;
use RuntimeException;

class Number
{
    use Macroable;

    /**
     * The current number.
     *
     * @var int|float
     */
    protected $number;

    /**
     * The current locale.
     *
     * @var string
     */
    protected $locale;

    /**
     * Creates a new instance of the class and sets the number and locale properties.
     *
     * @param  int|float $number
     * @param  string $locale
     * @return self
     */
    public static function create(int|float $number, string $locale = 'en')
    {
        $instants = new static;

        $instants->number = $number;

        $instants->locale = $locale;

        return $instants;
    }

    /**
     * Formats the number with the specified precision and maximum precision.
     *
     * @param  int|null  $precision
     * @param  int|null  $maxPrecision
     * @return string|false
     */
    public function format(?int $precision = null, ?int $maxPrecision = null)
    {
        $this->ensureIntlExtensionIsInstalled();

        $formatter = new NumberFormatter($this->locale, NumberFormatter::DECIMAL);

        if (!is_null($maxPrecision)) {
            $formatter->setAttribute(NumberFormatter::MAX_FRACTION_DIGITS, $maxPrecision);
        } elseif (!is_null($precision)) {
            $formatter->setAttribute(NumberFormatter::FRACTION_DIGITS, $precision);
        }

        return $formatter->format($this->number);
    }

    /**
     * Formats the number with a unit.
     *
     * @param  int  $precision
     * @param  int|null  $maxPrecision
     * @return string
     */
    public function formatWithUnit(int $precision = 0, ?int $maxPrecision = null)
    {
        $number = $this->number;

        $numberExponent = floor(log10($number));
        $displayExponent = $numberExponent - ($numberExponent % 3);
        $number /= pow(10, $displayExponent);
        $units = [
            3 => 'thousand',
            6 => 'million',
            9 => 'billion',
            12 => 'trillion',
            15 => 'quadrillion',
        ];

        return trim(sprintf('%s %s', static::create($number, $this->locale)->format($precision, $maxPrecision), $units[$displayExponent] ?? ''));
    }

    /**
     * Spell out the given number in the given locale.
     *
     * @return string
     */
    public function toSpell()
    {
        $this->ensureIntlExtensionIsInstalled();

        $formatter = new NumberFormatter($this->locale, NumberFormatter::SPELLOUT);

        return $formatter->format($this->number);
    }

    /**
     * Convert the given number to ordinal form.
     *
     * @return string
     */
    public function toOrdinal()
    {
        $this->ensureIntlExtensionIsInstalled();

        $formatter = new NumberFormatter($this->locale, NumberFormatter::ORDINAL);

        return $formatter->format($this->number);
    }

    /**
     * Convert the given number to its percentage equivalent.
     *
     * @param  int  $precision
     * @param  int|null  $maxPrecision
     * @return string|false
     */
    public function toPercentage(int $precision = 0, ?int $maxPrecision = null)
    {
        $this->ensureIntlExtensionIsInstalled();

        $formatter = new NumberFormatter($this->locale, NumberFormatter::PERCENT);

        if (is_null($maxPrecision)) {
            $formatter->setAttribute(NumberFormatter::FRACTION_DIGITS, $precision);
        } else {
            $formatter->setAttribute(NumberFormatter::MAX_FRACTION_DIGITS, $maxPrecision);
        }

        return $formatter->format($this->number / 100);
    }

    /**
     * Convert the given number to its currency equivalent.
     *
     * @param  string  $in
     * @return string|false
     */
    public function toCurrency(string $in = 'USD')
    {
        $this->ensureIntlExtensionIsInstalled();

        $formatter = new NumberFormatter($this->locale, NumberFormatter::CURRENCY);

        return $formatter->formatCurrency($this->number, $in);
    }

    /**
     * Convert the given number to its file size equivalent.
     *
     * @param  int  $precision
     * @param  int|null  $maxPrecision
     * @return string
     */
    public function toFileSize(int $precision = 0, ?int $maxPrecision = null)
    {
        $bytes = $this->number;

        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];

        for ($i = 0; ($bytes / 1024) > 0.9 && ($i < count($units) - 1); $i++) {
            $bytes /= 1024;
        }

        return sprintf('%s %s', static::create($bytes, $this->locale)->format($precision, $maxPrecision), $units[$i]);
    }

    /**
     * Convert the number to its human readable equivalent.
     *
     * @param  int  $precision
     * @param  int|null  $maxPrecision
     * @return string
     */
    public function toHumans(int $precision = 0, ?int $maxPrecision = null)
    {
        $number = $this->number;

        return match (true) {
            $number === 0 => '0',
            $number < 0 => sprintf('-%s', static::create(abs($number), $this->locale)->toHumans($precision, $maxPrecision)),
            $number >= 1e15 => sprintf('%s quadrillion', static::create($number / 1e15, $this->locale)->toHumans($precision, $maxPrecision)),
            default => $this->formatWithUnit($precision, $maxPrecision),
        };
    }

    /**
     * Ensure the "intl" PHP exntension is installed.
     *
     * @return void
     */
    protected function ensureIntlExtensionIsInstalled()
    {
        if (! extension_loaded('intl')) {
            throw new RuntimeException('The "intl" PHP extension is required to use this method.');
        }
    }
}
