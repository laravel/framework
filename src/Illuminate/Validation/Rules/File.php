<?php

namespace Illuminate\Validation\Rules;

use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Contracts\Validation\ValidatorAwareRule;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\Conditionable;
use Illuminate\Support\Traits\Macroable;
use InvalidArgumentException;

class File implements DataAwareRule, Rule, ValidatorAwareRule
{
    use Conditionable, Macroable;

    /**
     * Binary units flag used for size validation.
     */
    protected const BINARY = 'binary';

    /**
     * International units flag used for size validation.
     */
    protected const INTERNATIONAL = 'international';

    /**
     * The MIME types that the given file should match. This array may also contain file extensions.
     *
     * @var array
     */
    protected $allowedMimetypes = [];

    /**
     * The extensions that the given file should match.
     *
     * @var array
     */
    protected $allowedExtensions = [];

    /**
     * The minimum file size that the file can be.
     *
     * @var null|int
     */
    protected $minimumFileSize = null;

    /**
     * The maximum file size that the file can be.
     *
     * @var null|int
     */
    protected $maximumFileSize = null;

    /**
     * The units used for size validation.
     */
    protected string $units = self::INTERNATIONAL;

    /**
     * An array of custom rules that will be merged into the validation rules.
     *
     * @var array
     */
    protected $customRules = [];

    /**
     * The error message after validation, if any.
     *
     * @var array
     */
    protected $messages = [];

    /**
     * The data under validation.
     *
     * @var array
     */
    protected $data;

    /**
     * The validator performing the validation.
     *
     * @var \Illuminate\Validation\Validator
     */
    protected $validator;

    /**
     * The callback that will generate the "default" version of the file rule.
     *
     * @var string|array|callable|null
     */
    public static $defaultCallback;

    /**
     * Set the default callback to be used for determining the file default rules.
     *
     * If no arguments are passed, the default file rule configuration will be returned.
     *
     * @param  static|callable|null  $callback
     * @return static|void
     */
    public static function defaults($callback = null)
    {
        if (is_null($callback)) {
            return static::default();
        }

        if (! is_callable($callback) && ! $callback instanceof static) {
            throw new InvalidArgumentException('The given callback should be callable or an instance of '.static::class);
        }

        static::$defaultCallback = $callback;
    }

    /**
     * Get the default configuration of the file rule.
     *
     * @return static
     */
    public static function default()
    {
        $file = is_callable(static::$defaultCallback)
            ? call_user_func(static::$defaultCallback)
            : static::$defaultCallback;

        return $file instanceof Rule ? $file : new self;
    }

    /**
     * Limit the uploaded file to only image types.
     *
     * @param  bool  $allowSvg
     * @return ImageFile
     */
    public static function image($allowSvg = false)
    {
        return new ImageFile($allowSvg);
    }

    /**
     * Limit the uploaded file to the given MIME types or file extensions.
     *
     * @param  string|array<int, string>  $mimetypes
     * @return static
     */
    public static function types($mimetypes)
    {
        return tap(new static, fn ($file) => $file->allowedMimetypes = (array) $mimetypes);
    }

    /**
     * Limit the uploaded file to the given file extensions.
     *
     * @param  string|array<int, string>  $extensions
     * @return $this
     */
    public function extensions($extensions)
    {
        $this->allowedExtensions = (array) $extensions;

        return $this;
    }

    /**
     * Set the units for size validation to binary (1024-based).
     */
    public function binary(): static
    {
        $this->units = self::BINARY;

        return $this;
    }

    /**
     * Set the units for size validation to international (1000-based).
     */
    public function international(): static
    {
        $this->units = self::INTERNATIONAL;

        return $this;
    }

    /**
     * Indicate that the uploaded file should be exactly a certain size.
     */
    public function size(string|int $size): static
    {
        $this->minimumFileSize = $this->toKilobytes($size);
        $this->maximumFileSize = $this->minimumFileSize;

        return $this;
    }

    /**
     * Indicate that the uploaded file should be between a minimum and maximum size.
     */
    public function between(string|int $minSize, string|int $maxSize): static
    {
        $this->minimumFileSize = $this->toKilobytes($minSize);
        $this->maximumFileSize = $this->toKilobytes($maxSize);

        return $this;
    }

    /**
     * Indicate that the uploaded file should be no less than the given size.
     */
    public function min(string|int $size): static
    {
        $this->minimumFileSize = $this->toKilobytes($size);

        return $this;
    }

    /**
     * Indicate that the uploaded file should be no more than the given size.
     */
    public function max(string|int $size): static
    {
        $this->maximumFileSize = $this->toKilobytes($size);

        return $this;
    }

    /**
     * Convert a potentially human-friendly file size to kilobytes.
     *
     * Supports suffix detection with precedence over instance settings.
     */
    protected function toKilobytes(string|int $size): float|int
    {
        if (! is_string($size)) {
            return $size;
        }

        if (($value = $this->parseSize($size)) === false || $value < 0) {
            throw new InvalidArgumentException('Invalid numeric value in file size.');
        }

        return $this->detectUnits($size) === self::BINARY
            ? $this->toBinaryKilobytes($size, $value)
            : $this->toInternationalKilobytes($size, $value);
    }

    /**
     * Parse the numeric portion from a file size string.
     */
    protected function parseSize($size): false|float
    {
        return filter_var(
            is_numeric($size)
                ? $size
                : Str::before(trim($size), Str::match('/[a-zA-Z]/', trim($size))),
            FILTER_VALIDATE_FLOAT, FILTER_FLAG_ALLOW_THOUSAND
        );
    }

    /**
     * Detect the suffix and determine appropriate units from a file size string
     *
     * @throws InvalidArgumentException
     */
    protected function detectUnits(string $size): string
    {
        return match (true) {
            is_numeric($size) => $this->units,
            $this->isBinarySizeString($size) => self::BINARY,
            $this->isInternationalSizeString($size) => self::INTERNATIONAL,
            default => throw new InvalidArgumentException(
                "Invalid file size; units must be one of [kib, mib, gib, tib, kb, mb, gb, tb]. Given: {$size}."
            ),
        };
    }

    protected function isBinarySizeString(string $size): bool
    {
        return in_array(
            strtolower(substr(trim($size), -3)),
            ['kib', 'mib', 'gib', 'tib'],
            true
        );
    }

    protected function isInternationalSizeString(string $size): bool
    {
        return in_array(
            strtolower(substr(trim($size), -2)),
            ['kb', 'mb', 'gb', 'tb'],
            true
        );
    }

    /**
     * Convert a human-friendly file size to kilobytes using the International System.
     */
    protected function toInternationalKilobytes(string $size, float $value): float|int
    {
        return round($this->protectValueFromOverflow(
            $this->prepareValueForPrecision($value),
            ! is_numeric($size) ? $this->getInternationalMultiplier($size) : 1
        ));
    }

    /**
     * Get the international multiplier for a given size string.
     */
    protected function getInternationalMultiplier(string $size): int
    {
        return match (strtolower(substr(trim($size), -2))) {
            'kb' => 1,
            'mb' => 1_000,
            'gb' => 1_000_000,
            'tb' => 1_000_000_000,
        };
    }

    /**
     * Convert a human-friendly file size to kilobytes using the Binary System.
     */
    protected function toBinaryKilobytes(string $size, float $value): float|int
    {
        return round($this->protectValueFromOverflow(
            $this->prepareValueForPrecision($value),
            ! is_numeric($size) ? $this->getBinaryMultiplier($size) : 1
        ));
    }

    /**
     * Get the binary multiplier for a given size string.
     */
    protected function getBinaryMultiplier(string $size): int
    {
        return match (strtolower(substr(trim($size), -3))) {
            'kib' => 1,
            'mib' => 1_024,
            'gib' => 1_048_576,
            'tib' => 1_073_741_824,
        };
    }

    /**
     * Converts whole numbers to integers for exact arithmetic while keeping
     * fractional numbers as floats; also provides overflow protection by
     * falling back to float arithmetic for values too large for integer range.
     */
    protected function prepareValueForPrecision(float $value): float|int
    {
        return $value > PHP_INT_MAX
            || $value < PHP_INT_MIN
            || ((float) (int) $value) !== $value
                ? $value
                : (int) $value;
    }

    /**
     * Protect calculations from integer overflow by switching to float arithmetic when necessary.
     */
    protected function protectValueFromOverflow(float|int $value, int $multiplier): float|int
    {
        return $value > PHP_INT_MAX / $multiplier
            || $value < PHP_INT_MIN / $multiplier
            || is_float($value)
                ? (float) $value * $multiplier
                : (int) $value * $multiplier;
    }

    /**
     * Specify additional validation rules that should be merged with the default rules during validation.
     *
     * @param  string|array  $rules
     * @return $this
     */
    public function rules($rules)
    {
        $this->customRules = array_merge($this->customRules, Arr::wrap($rules));

        return $this;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        $this->messages = [];

        $validator = Validator::make(
            $this->data,
            [$attribute => $this->buildValidationRules()],
            $this->validator->customMessages,
            $this->validator->customAttributes
        );

        if ($validator->fails()) {
            return $this->fail($validator->messages()->all());
        }

        return true;
    }

    /**
     * Build the array of underlying validation rules based on the current state.
     *
     * @return array
     */
    protected function buildValidationRules()
    {
        $rules = ['file'];

        $rules = array_merge($rules, $this->buildMimetypes());

        if (! empty($this->allowedExtensions)) {
            $rules[] = 'extensions:'.implode(',', array_map(strtolower(...), $this->allowedExtensions));
        }

        $rule = match (true) {
            $this->minimumFileSize === null && $this->maximumFileSize === null => null,
            $this->maximumFileSize === null => "min:{$this->minimumFileSize}",
            $this->minimumFileSize === null => "max:{$this->maximumFileSize}",
            $this->minimumFileSize === $this->maximumFileSize => "size:{$this->minimumFileSize}",
            default => "between:{$this->minimumFileSize},{$this->maximumFileSize}",
        };

        if ($rule) {
            $rules[] = $rule;
        }

        return array_merge(array_filter($rules), $this->customRules);
    }

    /**
     * Separate the given mimetypes from extensions and return an array of correct rules to validate against.
     *
     * @return array
     */
    protected function buildMimetypes()
    {
        if (count($this->allowedMimetypes) === 0) {
            return [];
        }

        $rules = [];

        $mimetypes = array_filter(
            $this->allowedMimetypes,
            fn ($type) => str_contains($type, '/')
        );

        $mimes = array_diff($this->allowedMimetypes, $mimetypes);

        if (count($mimetypes) > 0) {
            $rules[] = 'mimetypes:'.implode(',', $mimetypes);
        }

        if (count($mimes) > 0) {
            $rules[] = 'mimes:'.implode(',', $mimes);
        }

        return $rules;
    }

    /**
     * Adds the given failures, and return false.
     *
     * @param  array|string  $messages
     * @return bool
     */
    protected function fail($messages)
    {
        $messages = Collection::wrap($messages)
            ->map(fn ($message) => $this->validator->getTranslator()->get($message))
            ->all();

        $this->messages = array_merge($this->messages, $messages);

        return false;
    }

    /**
     * Get the validation error message.
     *
     * @return array
     */
    public function message()
    {
        return $this->messages;
    }

    /**
     * Set the current validator.
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     * @return $this
     */
    public function setValidator($validator)
    {
        $this->validator = $validator;

        return $this;
    }

    /**
     * Set the current data under validation.
     *
     * @param  array  $data
     * @return $this
     */
    public function setData($data)
    {
        $this->data = $data;

        return $this;
    }
}
