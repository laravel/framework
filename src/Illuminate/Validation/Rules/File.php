<?php

namespace Illuminate\Validation\Rules;

use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Contracts\Validation\ValidatorAwareRule;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Traits\Conditionable;
use Illuminate\Support\Traits\Macroable;
use InvalidArgumentException;

class File implements Rule, DataAwareRule, ValidatorAwareRule
{
    use Conditionable;
    use Macroable;

    /**
     * The MIME types that the given file should match. This array may also contain file extensions.
     *
     * @var array
     */
    protected $allowedMimetypes = [];

    /**
     * The minimum size in kilobytes that the file can be.
     *
     * @var null|int
     */
    protected $minimumFileSize = null;

    /**
     * The maximum size in kilobytes that the file can be.
     *
     * @var null|int
     */
    protected $maximumFileSize = null;

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
     * @return static|null
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

        return $file instanceof Rule ? $file : new self();
    }

    /**
     * Limit the uploaded file to only image types.
     *
     * @return ImageFile
     */
    public static function image()
    {
        return new ImageFile();
    }

    /**
     * Limit the uploaded file to the given MIME types or file extensions.
     *
     * @param  string|array<int, string>  $mimetypes
     * @return static
     */
    public static function types($mimetypes)
    {
        return tap(new static(), fn ($file) => $file->allowedMimetypes = (array) $mimetypes);
    }

    /**
     * Indicate that the uploaded file should be exactly a certain size in kilobytes.
     *
     * @param  int  $kilobytes
     * @return $this
     */
    public function size($kilobytes)
    {
        $this->minimumFileSize = $kilobytes;
        $this->maximumFileSize = $kilobytes;

        return $this;
    }

    /**
     * Indicate that the uploaded file should be between a minimum and maximum size in kilobytes.
     *
     * @param  int  $minKilobytes
     * @param  int  $maxKilobytes
     * @return $this
     */
    public function between($minKilobytes, $maxKilobytes)
    {
        $this->minimumFileSize = $minKilobytes;
        $this->maximumFileSize = $maxKilobytes;

        return $this;
    }

    /**
     * Indicate that the uploaded file should be no less than the given number of kilobytes.
     *
     * @param  int  $kilobytes
     * @return $this
     */
    public function min($kilobytes)
    {
        $this->minimumFileSize = $kilobytes;

        return $this;
    }

    /**
     * Indicate that the uploaded file should be no more than the given number of kilobytes.
     *
     * @param  int  $kilobytes
     * @return $this
     */
    public function max($kilobytes)
    {
        $this->maximumFileSize = $kilobytes;

        return $this;
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

        $rules[] = match (true) {
            is_null($this->minimumFileSize) && is_null($this->maximumFileSize) => null,
            is_null($this->maximumFileSize) => "min:{$this->minimumFileSize}",
            is_null($this->minimumFileSize) => "max:{$this->maximumFileSize}",
            $this->minimumFileSize !== $this->maximumFileSize => "between:{$this->minimumFileSize},{$this->maximumFileSize}",
            default => "size:{$this->minimumFileSize}",
        };

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
        $messages = collect(Arr::wrap($messages))->map(function ($message) {
            return $this->validator->getTranslator()->get($message);
        })->all();

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
