<?php

namespace Illuminate\Support;

use DivisionByZeroError;
use Illuminate\Support\Traits\Conditionable;
use Illuminate\Support\Traits\Dumpable;
use Illuminate\Support\Traits\Macroable;
use Illuminate\Support\Traits\Tappable;
use InvalidArgumentException;
use JsonSerializable;
use RuntimeException;
use Stringable as BaseStringable;
use Throwable;

class Numberable implements BaseStringable, JsonSerializable
{
    use Conditionable, Dumpable, Macroable, Tappable;

    /**
     * The underlying number value.
     *
     * @var int|float
     */
    protected $value;

    /**
     * The locale used for locale-aware formatting and parsing.
     *
     * @var string|null
     */
    protected $locale;

    /**
     * The currency used for currency formatting.
     *
     * @var string|null
     */
    protected $currency;

    /**
     * The fixed precision used by formatting operations.
     *
     * @var int|null
     */
    protected $precision;

    /**
     * The max precision used by formatting operations.
     *
     * @var int|null
     */
    protected $maxPrecision;

    /**
     * Remember the last requested cast format.
     *
     * @var array{type: string, payload: mixed}|null
     */
    protected $stringCast;

    /**
     * Custom, user-defined formatters.
     *
     * @var array<string, callable(int|float, static, array): string>
     */
    protected static $customFormats = [];

    /**
     * Create a new instance of the class.
     *
     * @param  int|float  $value
     */
    public function __construct(int|float $value = 0)
    {
        $this->value = $value;
    }

    /**
     * Get a new numberable instance.
     *
     * @param  int|float  $value
     * @return static
     */
    public static function make(int|float $value = 0)
    {
        return new static($value);
    }

    /**
     * Parse a number string into a new numberable instance.
     *
     * @param  string  $value
     * @param  string|null  $locale
     * @return static
     */
    public static function parse(string $value, ?string $locale = null)
    {
        $parsed = static::parseNumber($value, $locale);

        if ($parsed === false) {
            throw new InvalidArgumentException('Unable to parse the given numeric string.');
        }

        return (new static((float) $parsed))->withLocaleIfPresent($locale);
    }

    /**
     * Parse a number string into an integer numberable instance.
     *
     * @param  string  $value
     * @param  string|null  $locale
     * @return static
     */
    public static function parseInt(string $value, ?string $locale = null)
    {
        $parsed = static::parseNumber($value, $locale, type: 'int');

        if ($parsed === false) {
            throw new InvalidArgumentException('Unable to parse the given numeric string.');
        }

        return (new static((int) $parsed))->withLocaleIfPresent($locale);
    }

    /**
     * Parse a number string into a float numberable instance.
     *
     * @param  string  $value
     * @param  string|null  $locale
     * @return static
     */
    public static function parseFloat(string $value, ?string $locale = null)
    {
        $parsed = static::parseNumber($value, $locale, type: 'float');

        if ($parsed === false) {
            throw new InvalidArgumentException('Unable to parse the given numeric string.');
        }

        return (new static((float) $parsed))->withLocaleIfPresent($locale);
    }

    /**
     * Add a value to the number.
     *
     * @param  int|float  $value
     * @return static
     */
    public function add(int|float $value = 1)
    {
        return $this->newInstance(static::normalizeNumber($this->value + $value));
    }

    /**
     * Subtract a value from the number.
     *
     * @param  int|float  $value
     * @return static
     */
    public function subtract(int|float $value = 1)
    {
        return $this->newInstance(static::normalizeNumber($this->value - $value));
    }

    /**
     * Multiply the number by a value.
     *
     * @param  int|float  $value
     * @return static
     */
    public function multiply(int|float $value)
    {
        return $this->newInstance(static::normalizeNumber($this->value * $value));
    }

    /**
     * Divide the number by a value.
     *
     * @param  int|float  $value
     * @return static
     */
    public function divide(int|float $value)
    {
        if ((float) $value === 0.0) {
            throw new DivisionByZeroError('Division by zero.');
        }

        return $this->newInstance(static::normalizeNumber($this->value / $value));
    }

    /**
     * Get the modulo of the number by a value.
     *
     * @param  int|float  $value
     * @return static
     */
    public function mod(int|float $value)
    {
        if ((float) $value === 0.0) {
            throw new DivisionByZeroError('Modulo by zero.');
        }

        if (is_int($this->value) && is_int($value)) {
            return $this->newInstance($this->value % $value);
        }

        return $this->newInstance(static::normalizeNumber(fmod((float) $this->value, (float) $value)));
    }

    /**
     * Raise the number to a power.
     *
     * @param  int|float  $exponent
     * @return static
     */
    public function pow(int|float $exponent)
    {
        return $this->newInstance(static::normalizeNumber($this->value ** $exponent));
    }

    /**
     * Get the absolute value.
     *
     * @return static
     */
    public function abs()
    {
        return $this->newInstance(static::normalizeNumber(abs($this->value)));
    }

    /**
     * Round the number.
     *
     * @param  int  $precision
     * @return static
     */
    public function round(int $precision = 0)
    {
        return $this->newInstance(static::normalizeNumber(round($this->value, $precision)));
    }

    /**
     * Floor the number.
     *
     * @return static
     */
    public function floor()
    {
        return $this->newInstance(static::normalizeNumber(floor($this->value)));
    }

    /**
     * Ceil the number.
     *
     * @return static
     */
    public function ceil()
    {
        return $this->newInstance(static::normalizeNumber(ceil($this->value)));
    }

    /**
     * Format the number using decimal separators.
     *
     * @param  int  $decimals
     * @param  string  $decimalSeparator
     * @param  string  $thousandsSeparator
     * @return string
     */
    public function format(int $decimals = 2, string $decimalSeparator = '.', string $thousandsSeparator = ',')
    {
        if ($decimals < 0) {
            throw new InvalidArgumentException('Precision must be greater than or equal to zero.');
        }

        $decimals = $this->precision ?? $decimals;

        $this->rememberStringCast('format', [$decimals, $decimalSeparator, $thousandsSeparator]);

        return $this->formatValue($decimals, $decimalSeparator, $thousandsSeparator);
    }

    /**
     * Format the number using a named style.
     *
     * @param  string  $style
     * @param  array  $options
     * @return string
     */
    public function formatAs(string $style, array $options = [])
    {
        $this->rememberStringCast('formatAs', [$style, $options]);

        return $this->formatAsValue($style, $options);
    }

    /**
     * Register a custom formatter.
     *
     * @param  string  $style
     * @param  callable(int|float, static, array): string  $callback
     * @return void
     */
    public static function registerFormat(string $style, callable $callback)
    {
        static::$customFormats[static::normalizeStyle($style)] = $callback;
    }

    /**
     * Get a key/value representation of the whole and fractional parts.
     *
     * @return array{whole: int, fraction: int}
     */
    public function pairs(): array
    {
        $absolute = abs((float) $this->value);
        $whole = $this->value < 0 ? (int) ceil($this->value) : (int) floor($this->value);
        $fraction = 0;

        $formatted = rtrim(rtrim(sprintf('%.14F', $absolute), '0'), '.');

        if (($position = strpos($formatted, '.')) !== false) {
            $fraction = (int) substr($formatted, $position + 1);
        }

        return ['whole' => $whole, 'fraction' => $fraction];
    }

    /**
     * Determine if the underlying value is an integer.
     *
     * @return bool
     */
    public function isInt()
    {
        return is_int($this->value)
            || (is_float($this->value) && is_finite($this->value) && floor($this->value) === $this->value);
    }

    /**
     * Determine if the underlying value is a non-integer float.
     *
     * @return bool
     */
    public function isFloat()
    {
        return is_float($this->value) && ! $this->isInt();
    }

    /**
     * Determine if the value is positive.
     *
     * @return bool
     */
    public function isPositive()
    {
        return $this->value > 0;
    }

    /**
     * Determine if the value is negative.
     *
     * @return bool
     */
    public function isNegative()
    {
        return $this->value < 0;
    }

    /**
     * Set the locale used for parsing/formatting.
     *
     * @param  string  $locale
     * @return static
     */
    public function withLocale(string $locale)
    {
        $instance = $this->newInstance($this->value);
        $instance->locale = $locale;

        return $instance;
    }

    /**
     * Set the currency used for currency formatting.
     *
     * @param  string  $currency
     * @return static
     */
    public function withCurrency(string $currency)
    {
        $instance = $this->newInstance($this->value);
        $instance->currency = $currency;

        return $instance;
    }

    /**
     * Set a fixed precision for formatting.
     *
     * @param  int  $precision
     * @return static
     */
    public function withPrecision(int $precision)
    {
        if ($precision < 0) {
            throw new InvalidArgumentException('Precision must be greater than or equal to zero.');
        }

        $instance = $this->newInstance($this->value);
        $instance->precision = $precision;

        return $instance;
    }

    /**
     * Set a max precision for formatting.
     *
     * @param  int  $maxPrecision
     * @return static
     */
    public function withMaxPrecision(int $maxPrecision)
    {
        if ($maxPrecision < 0) {
            throw new InvalidArgumentException('Precision must be greater than or equal to zero.');
        }

        $instance = $this->newInstance($this->value);
        $instance->maxPrecision = $maxPrecision;

        return $instance;
    }

    /**
     * Get the underlying numeric value.
     *
     * @return int|float
     */
    public function value()
    {
        return $this->value;
    }

    /**
     * Get the underlying value as an integer.
     *
     * @return int
     */
    public function toInt()
    {
        return (int) $this->value;
    }

    /**
     * Get the underlying value as a float.
     *
     * @return float
     */
    public function toFloat()
    {
        return (float) $this->value;
    }

    /**
     * Convert the object to a string when JSON encoded.
     *
     * @return int|float
     */
    public function jsonSerialize(): int|float
    {
        return $this->value;
    }

    /**
     * Get the string representation for the instance.
     *
     * @return string
     */
    public function toString()
    {
        return $this->__toString();
    }

    /**
     * Get the string representation of the number.
     *
     * @return string
     */
    public function __toString()
    {
        try {
            return $this->stringCast !== null
                ? $this->formatForStringCast()
                : $this->defaultStringRepresentation();
        } catch (Throwable) {
            return '';
        }
    }

    /**
     * Parse a numeric string.
     *
     * @param  string  $value
     * @param  string|null  $locale
     * @param  string  $type
     * @return int|float|false
     */
    protected static function parseNumber(string $value, ?string $locale = null, string $type = 'float'): int|float|false
    {
        $parsed = static::parseNumberFallback($value, $locale);

        if ($parsed !== false) {
            return match ($type) {
                'int' => (int) $parsed,
                'float' => (float) $parsed,
                default => $parsed,
            };
        }

        if (extension_loaded('intl')) {
            return match ($type) {
                'int' => Number::parseInt($value, $locale),
                'float' => Number::parseFloat($value, $locale),
                default => Number::parse($value, locale: $locale),
            };
        }

        return false;
    }

    /**
     * Parse a numeric string when the intl extension is unavailable.
     *
     * @param  string  $value
     * @param  string|null  $locale
     * @return float|false
     */
    protected static function parseNumberFallback(string $value, ?string $locale = null): float|false
    {
        $value = trim($value);

        if ($value === '') {
            return false;
        }

        $normalized = str_replace(["\xC2\xA0", "\xE2\x80\xAF", ' ', "'", '’', '_'], '', $value);
        $decimalSeparator = static::resolveDecimalSeparator($normalized, $locale);

        if ($decimalSeparator === ',') {
            $normalized = str_replace('.', '', $normalized);
            $normalized = str_replace(',', '.', $normalized);
        } else {
            $normalized = str_replace(',', '', $normalized);
        }

        if (! preg_match('/^[+-]?(?:\d+\.?\d*|\.\d+)$/', $normalized)) {
            return false;
        }

        return (float) $normalized;
    }

    /**
     * Determine the decimal separator for fallback parsing.
     *
     * @param  string  $value
     * @param  string|null  $locale
     * @return string
     */
    protected static function resolveDecimalSeparator(string $value, ?string $locale = null): string
    {
        if (static::localeUsesCommaDecimal($locale)) {
            return ',';
        }

        if (! is_null($locale)) {
            return '.';
        }

        $lastComma = strrpos($value, ',');
        $lastDot = strrpos($value, '.');

        if ($lastComma !== false && $lastDot !== false) {
            return $lastComma > $lastDot ? ',' : '.';
        }

        if ($lastComma !== false) {
            $digitsAfter = strlen($value) - $lastComma - 1;

            return $digitsAfter >= 1 && $digitsAfter <= 2 ? ',' : '.';
        }

        return '.';
    }

    /**
     * Determine if the locale generally uses commas as decimal separators.
     *
     * @param  string|null  $locale
     * @return bool
     */
    protected static function localeUsesCommaDecimal(?string $locale): bool
    {
        if (is_null($locale)) {
            return false;
        }

        $locale = strtolower(str_replace('_', '-', $locale));

        foreach ([
            'de', 'fr', 'es', 'it', 'pt', 'nl', 'ru', 'sv', 'da', 'fi',
            'nb', 'nn', 'pl', 'cs', 'sk', 'tr', 'uk', 'ro', 'hu', 'bg',
        ] as $prefix) {
            if (str_starts_with($locale, $prefix)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Format a number using number_format.
     *
     * @param  int  $decimals
     * @param  string  $decimalSeparator
     * @param  string  $thousandsSeparator
     * @return string
     */
    protected function formatValue(int $decimals, string $decimalSeparator = '.', string $thousandsSeparator = ','): string
    {
        return number_format($this->value, $decimals, $decimalSeparator, $thousandsSeparator);
    }

    /**
     * Format a number using a named style.
     *
     * @param  string  $style
     * @param  array  $options
     * @return string
     */
    protected function formatAsValue(string $style, array $options = []): string
    {
        $normalizedStyle = static::normalizeStyle($style);

        if (isset(static::$customFormats[$normalizedStyle])) {
            return (string) call_user_func(static::$customFormats[$normalizedStyle], $this->value, $this, $options);
        }

        return match ($normalizedStyle) {
            'currency' => $this->formatCurrencyStyle($options),
            'percentage' => $this->formatPercentageStyle($options),
            'spell' => $this->formatSpellStyle($options),
            'ordinal' => $this->formatOrdinalStyle($options),
            'spellordinal' => $this->formatSpellOrdinalStyle($options),
            'abbreviated' => $this->formatAbbreviatedStyle($options),
            'summarized', 'humanreadable' => $this->formatSummarizedStyle($options),
            'filesize' => $this->formatFileSizeStyle($options),
            default => throw new InvalidArgumentException('Unsupported number format style ['.$style.'].'),
        };
    }

    /**
     * Format as currency.
     *
     * @param  array  $options
     * @return string
     */
    protected function formatCurrencyStyle(array $options): string
    {
        $currency = (string) ($options['currency'] ?? $this->currency ?? Number::defaultCurrency());
        $locale = $this->resolveLocale($options);
        $precision = $this->resolvePrecision($options);

        return $this->intlOrFallback(
            fn () => (string) Number::currency($this->value, $currency, $locale, $precision),
            fn () => $this->fallbackCurrency($currency, $precision ?? 2)
        );
    }

    /**
     * Format as a percentage.
     *
     * @param  array  $options
     * @return string
     */
    protected function formatPercentageStyle(array $options): string
    {
        $precision = $this->resolvePrecision($options) ?? 0;
        $maxPrecision = $this->resolveMaxPrecision($options);
        $locale = $this->resolveLocale($options);

        return $this->intlOrFallback(
            fn () => (string) Number::percentage($this->value, $precision, $maxPrecision, $locale),
            fn () => $this->fallbackPercentage($precision, $maxPrecision)
        );
    }

    /**
     * Format as spelled-out text.
     *
     * @param  array  $options
     * @return string
     */
    protected function formatSpellStyle(array $options): string
    {
        $locale = $this->resolveLocale($options);

        return $this->intlOrFallback(
            fn () => Number::spell($this->value, $locale),
            fn () => $this->defaultStringRepresentation()
        );
    }

    /**
     * Format as an ordinal.
     *
     * @param  array  $options
     * @return string
     */
    protected function formatOrdinalStyle(array $options): string
    {
        $locale = $this->resolveLocale($options);

        return $this->intlOrFallback(
            fn () => Number::ordinal($this->value, $locale),
            fn () => $this->fallbackOrdinal()
        );
    }

    /**
     * Format as a spelled-out ordinal.
     *
     * @param  array  $options
     * @return string
     */
    protected function formatSpellOrdinalStyle(array $options): string
    {
        $locale = $this->resolveLocale($options);

        return $this->intlOrFallback(
            fn () => Number::spellOrdinal($this->value, $locale),
            fn () => $this->fallbackOrdinal()
        );
    }

    /**
     * Format as an abbreviated value.
     *
     * @param  array  $options
     * @return string
     */
    protected function formatAbbreviatedStyle(array $options): string
    {
        $precision = $this->resolvePrecision($options) ?? 0;
        $maxPrecision = $this->resolveMaxPrecision($options);

        return $this->intlOrFallback(
            fn () => (string) Number::abbreviate($this->value, $precision, $maxPrecision),
            fn () => static::fallbackSummarize($this->value, $precision, $maxPrecision)
        );
    }

    /**
     * Format as a human-readable summarized value.
     *
     * @param  array  $options
     * @return string
     */
    protected function formatSummarizedStyle(array $options): string
    {
        $precision = $this->resolvePrecision($options) ?? 0;
        $maxPrecision = $this->resolveMaxPrecision($options);

        return $this->intlOrFallback(
            fn () => (string) Number::forHumans($this->value, $precision, $maxPrecision),
            fn () => static::fallbackSummarize($this->value, $precision, $maxPrecision, [
                3 => ' thousand',
                6 => ' million',
                9 => ' billion',
                12 => ' trillion',
                15 => ' quadrillion',
            ])
        );
    }

    /**
     * Format as file size.
     *
     * @param  array  $options
     * @return string
     */
    protected function formatFileSizeStyle(array $options): string
    {
        $precision = $this->resolvePrecision($options) ?? 0;
        $maxPrecision = $this->resolveMaxPrecision($options);

        return $this->intlOrFallback(
            fn () => Number::fileSize($this->value, $precision, $maxPrecision),
            fn () => static::fallbackFileSize($this->value, $precision, $maxPrecision)
        );
    }

    /**
     * Format the value with a locale-aware formatter.
     *
     * @param  int|float  $value
     * @param  int|null  $precision
     * @param  int|null  $maxPrecision
     * @param  string|null  $locale
     * @return string
     */
    protected function formatLocalized(int|float $value, ?int $precision = null, ?int $maxPrecision = null, ?string $locale = null): string
    {
        return $this->intlOrFallback(
            fn () => (string) Number::format($value, $precision, $maxPrecision, $locale),
            fn () => static::fallbackFormat($value, $precision, $maxPrecision)
        );
    }

    /**
     * Fallback formatting for decimal numbers.
     *
     * @param  int|float  $value
     * @param  int|null  $precision
     * @param  int|null  $maxPrecision
     * @return string
     */
    protected static function fallbackFormat(int|float $value, ?int $precision = null, ?int $maxPrecision = null): string
    {
        if (! is_null($precision)) {
            return number_format($value, $precision, '.', ',');
        }

        if (is_null($maxPrecision)) {
            return number_format($value, 0, '.', ',');
        }

        $formatted = number_format(round($value, $maxPrecision), $maxPrecision, '.', ',');

        return str_contains($formatted, '.')
            ? rtrim(rtrim($formatted, '0'), '.')
            : $formatted;
    }

    /**
     * Fallback formatting for currency.
     *
     * @param  string  $currency
     * @param  int  $precision
     * @return string
     */
    protected function fallbackCurrency(string $currency, int $precision): string
    {
        $prefix = $this->value < 0 ? '-' : '';

        return $prefix.$currency.' '.static::fallbackFormat(abs($this->value), $precision);
    }

    /**
     * Fallback formatting for percentages.
     *
     * @param  int  $precision
     * @param  int|null  $maxPrecision
     * @return string
     */
    protected function fallbackPercentage(int $precision, ?int $maxPrecision = null): string
    {
        $formatted = static::fallbackFormat($this->value, $precision, $maxPrecision);

        return $formatted.'%';
    }

    /**
     * Fallback formatting for ordinals.
     *
     * @return string
     */
    protected function fallbackOrdinal(): string
    {
        $number = (int) $this->value;
        $absolute = abs($number);
        $suffix = 'th';

        if (($absolute % 100 < 11) || ($absolute % 100 > 13)) {
            $suffix = match ($absolute % 10) {
                1 => 'st',
                2 => 'nd',
                3 => 'rd',
                default => 'th',
            };
        }

        return $number.$suffix;
    }

    /**
     * Fallback summarize formatter.
     *
     * @param  int|float  $number
     * @param  int  $precision
     * @param  int|null  $maxPrecision
     * @param  array<int, string>  $units
     * @return string
     */
    protected static function fallbackSummarize(int|float $number, int $precision = 0, ?int $maxPrecision = null, array $units = []): string
    {
        if (empty($units)) {
            $units = [
                3 => 'K',
                6 => 'M',
                9 => 'B',
                12 => 'T',
                15 => 'Q',
            ];
        }

        switch (true) {
            case (float) $number === 0.0:
                return $precision > 0 ? static::fallbackFormat(0, $precision, $maxPrecision) : '0';
            case $number < 0:
                return sprintf('-%s', static::fallbackSummarize(abs($number), $precision, $maxPrecision, $units));
            case $number >= 1e15:
                return sprintf('%s'.end($units), static::fallbackSummarize($number / 1e15, $precision, $maxPrecision, $units));
        }

        $numberExponent = floor(log10($number));
        $displayExponent = $numberExponent - ($numberExponent % 3);
        $number /= 10 ** $displayExponent;

        return trim(sprintf('%s%s', static::fallbackFormat($number, $precision, $maxPrecision), $units[$displayExponent] ?? ''));
    }

    /**
     * Fallback formatting for file size.
     *
     * @param  int|float  $bytes
     * @param  int  $precision
     * @param  int|null  $maxPrecision
     * @return string
     */
    protected static function fallbackFileSize(int|float $bytes, int $precision = 0, ?int $maxPrecision = null): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
        $unitCount = count($units);

        for ($i = 0; ($bytes / 1024) > 0.9 && ($i < $unitCount - 1); $i++) {
            $bytes /= 1024;
        }

        return sprintf('%s %s', static::fallbackFormat($bytes, $precision, $maxPrecision), $units[$i]);
    }

    /**
     * Execute an intl formatter with a fallback strategy.
     *
     * @template TReturn
     *
     * @param  callable(): TReturn  $intlFormatter
     * @param  callable(): TReturn  $fallbackFormatter
     * @return TReturn
     */
    protected function intlOrFallback(callable $intlFormatter, callable $fallbackFormatter)
    {
        try {
            $result = $intlFormatter();

            return $result !== false ? $result : $fallbackFormatter();
        } catch (RuntimeException $e) {
            if (! str_contains($e->getMessage(), '"intl"')) {
                throw $e;
            }

            return $fallbackFormatter();
        }
    }

    /**
     * Get the default string representation.
     *
     * @return string
     */
    protected function defaultStringRepresentation(): string
    {
        $precision = $this->precision;
        $maxPrecision = is_null($precision) ? ($this->maxPrecision ?? 14) : $this->maxPrecision;

        return $this->formatLocalized($this->value, $precision, $maxPrecision, $this->locale);
    }

    /**
     * Apply the remembered string cast strategy.
     *
     * @return string
     */
    protected function formatForStringCast(): string
    {
        return match ($this->stringCast['type']) {
            'format' => $this->formatValue(
                $this->stringCast['payload'][0],
                $this->stringCast['payload'][1],
                $this->stringCast['payload'][2]
            ),
            'formatAs' => $this->formatAsValue(
                $this->stringCast['payload'][0],
                $this->stringCast['payload'][1]
            ),
            default => $this->defaultStringRepresentation(),
        };
    }

    /**
     * Remember the last requested string cast format.
     *
     * @param  string  $type
     * @param  mixed  $payload
     * @return void
     */
    protected function rememberStringCast(string $type, mixed $payload): void
    {
        $this->stringCast = [
            'type' => $type,
            'payload' => $payload,
        ];
    }

    /**
     * Create a new instance carrying formatting preferences.
     *
     * @param  int|float  $value
     * @return static
     */
    protected function newInstance(int|float $value)
    {
        $instance = new static($value);
        $instance->locale = $this->locale;
        $instance->currency = $this->currency;
        $instance->precision = $this->precision;
        $instance->maxPrecision = $this->maxPrecision;
        $instance->stringCast = $this->stringCast;

        return $instance;
    }

    /**
     * Set locale if one is provided.
     *
     * @param  string|null  $locale
     * @return static
     */
    protected function withLocaleIfPresent(?string $locale)
    {
        return $locale ? $this->withLocale($locale) : $this;
    }

    /**
     * Normalize a style name.
     *
     * @param  string  $style
     * @return string
     */
    protected static function normalizeStyle(string $style): string
    {
        return strtolower(str_replace(['-', '_', ' '], '', $style));
    }

    /**
     * Normalize the numeric value.
     *
     * @param  int|float  $value
     * @return int|float
     */
    protected static function normalizeNumber(int|float $value): int|float
    {
        if (
            is_float($value)
            && is_finite($value)
            && floor($value) === $value
            && $value <= PHP_INT_MAX
            && $value >= PHP_INT_MIN
        ) {
            return (int) $value;
        }

        return $value;
    }

    /**
     * Resolve the locale for a formatting operation.
     *
     * @param  array  $options
     * @return string|null
     */
    protected function resolveLocale(array $options): ?string
    {
        return $options['locale'] ?? $this->locale;
    }

    /**
     * Resolve the precision for a formatting operation.
     *
     * @param  array  $options
     * @return int|null
     */
    protected function resolvePrecision(array $options): ?int
    {
        $precision = $options['precision'] ?? $this->precision;

        return is_null($precision) ? null : (int) $precision;
    }

    /**
     * Resolve the max precision for a formatting operation.
     *
     * @param  array  $options
     * @return int|null
     */
    protected function resolveMaxPrecision(array $options): ?int
    {
        $maxPrecision = $options['maxPrecision'] ?? $this->maxPrecision;

        return is_null($maxPrecision) ? null : (int) $maxPrecision;
    }
}
