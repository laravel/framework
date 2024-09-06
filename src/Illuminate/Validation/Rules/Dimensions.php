<?php

namespace Illuminate\Validation\Rules;

use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Contracts\Validation\ValidatorAwareRule;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Traits\Conditionable;
use Illuminate\Support\Traits\Macroable;
use Illuminate\Testing\Exceptions\InvalidArgumentException;

class Dimensions implements Rule, DataAwareRule, ValidatorAwareRule
{
    use Conditionable, Macroable;

    /**
     * The width constraint in pixels.
     *
     * @var null|int
     */
    protected $width = null;

    /**
     * The minimum size in pixels that the image can be.
     *
     * @var null|int
     */
    protected $minWidth = null;

    /**
     * The maximum size in pixels that the image can be.
     *
     * @var null|int
     */
    protected $maxWidth = null;

    /**
     * The height constraint in pixels.
     *
     * @var null|int
     */
    protected $height = null;

    /**
     * The minimum size in pixels that the image can be.
     *
     * @var null|int
     */
    protected $minHeight = null;

    /**
     * The maximum size in pixels that the image can be.
     *
     * @var null|int
     */
    protected $maxHeight = null;

    /**
     * The ratio constraint.
     *
     * @var null|float
     */
    protected $ratio = null;

    /**
     * The minimum aspect ratio constraint.
     *
     * @var null|float
     */
    protected $minRatio = null;

    /**
     * The maximum aspect ratio constraint.
     *
     * @var null|float
     */
    protected $maxRatio = null;

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
     * The callback that will generate the "default" version of the dimensions rule.
     *
     * @var string|array|callable|null
     */
    public static $defaultCallback;

    /**
     * Create a new dimensions rule instance.
     *
     * @param  array  $constraints
     * @return void
     */
    public function __construct(array $constraints = [])
    {
        foreach ($constraints as $constraint) {
            [$key, $value] = explode('=', $constraint);
            $key = Str::camel($key);

            if (method_exists($this, $key)) {
            	$this->{$key}($value);
            }
        }
    }

    /**
     * Set the "width" constraint.
     *
     * @param  int  $value
     * @return $this
     */
    public function width($value)
    {
        $this->width = $value;

        return $this;
    }

    /**
     * Set the "height" constraint.
     *
     * @param  int  $value
     * @return $this
     */
    public function height($value)
    {
        $this->height = $value;

        return $this;
    }

    /**
     * Set the "min width" constraint.
     *
     * @param  int  $value
     * @return $this
     */
    public function minWidth($value)
    {
        $this->minWidth = $value;

        return $this;
    }

    /**
     * Set the "min height" constraint.
     *
     * @param  int  $value
     * @return $this
     */
    public function minHeight($value)
    {
        $this->minHeight = $value;

        return $this;
    }

    /**
     * Set the "max width" constraint.
     *
     * @param  int  $value
     * @return $this
     */
    public function maxWidth($value)
    {
        $this->maxWidth =$value;

        return $this;
    }

    /**
     * Set the "max height" constraint.
     *
     * @param  int  $value
     * @return $this
     */
    public function maxHeight($value)
    {
        $this->maxHeight = $value;

        return $this;
    }

    /**
     * Set the width between constraint.
     *
     * @param   int  $min
     * @param   int  $max
     * @return  $this
     */
    public function widthBetween($min, $max)
    {
        $this->minWidth = $min;
        $this->maxWidth = $max;

        return $this;
    }

    /**
     * Set the height between constraint.
     *
     * @param  int  $min
     * @param  int  $max
     * @return  $this
     */
    public function heightBetween($min, $max)
    {
        $this->minHeight = $min;
        $this->maxHeight = $max;

        return $this;
    }

    /**
     * Set the "ratio" constraint.
     *
     * @param  float  $value
     * @return $this
     */
    public function ratio($value)
    {
        $this->ratio = $value;

        return $this;
    }

    /**
     * Set the minimum aspect ratio constraint.
     *
     * @param  float  $value
     * @return $this
     */
    public function minRatio($value)
    {
        $this->minRatio = $value;

        return $this;
    }

    /**
     * Set the maximum aspect ratio constraint.
     *
     * @param  float  $value
     * @return $this
     */
    public function maxRatio($value)
    {
        $this->maxRatio = $value;

        return $this;
    }

    /**
     * Set the aspect ratio range constraint.
     *
     * @param  float  $min
     * @param  float  $max
     * @return $this
     */
    public function ratioBetween($min, $max)
    {
        $this->minRatio = $min;
        $this->maxRatio = $max;

        return $this;
    }

    /**
     * Build the array of underlying validation rules based on the current state.
     *
     * @return array
     */
    protected function buildValidationRules()
    {
        $rules = [];

        if ($this->width) {
            $rules[] = "width:{$this->width}";
        }

        if ($this->minWidth || $this->maxWidth) {
            $rules[] = match (true) {
                is_null($this->maxWidth) => "min_width:{$this->minWidth}",
                is_null($this->minWidth) => "max_width:{$this->maxWidth}",
                $this->minWidth !== $this->maxWidth => "width_between:{$this->minWidth},{$this->maxWidth}",
                default => null,
            };
        }

        if ($this->height) {
            $rules[] = "height:{$this->height}";
        }

        if ($this->minHeight || $this->maxHeight) {
            $rules[] = match (true) {
                is_null($this->maxHeight) => "min_height:{$this->minHeight}",
                is_null($this->minHeight) => "max_height:{$this->maxHeight}",
                $this->minHeight !== $this->maxHeight => "height_between:{$this->minHeight},{$this->maxHeight}",
                default => null,
            };
        }

        if ($this->ratio) {
            $rules[] = "ratio:{$this->ratio}";
        }

        if ($this->minRatio || $this->maxRatio) {
            $rules[] = match (true) {
                is_null($this->maxRatio) => "min_ratio:{$this->minRatio}",
                is_null($this->minRatio) => "max_ratio:{$this->maxRatio}",
                $this->minRatio !== $this->maxRatio => "ratio_between:{$this->minRatio},{$this->maxRatio}",
                default => null,
            };
        }

        return array_filter($rules);
    }

    /**
     * Set the default callback to be used for determining the dimensions rule.
     *
     * If no arguments are passed, the default dimension rule configuration will be returned.
     *
     * @param  static|callable|null  $callback
     * @return static|null
     */
    public static function defaults($callback = null)
    {
        if ($callback === null) {
            return static::default();
        }

        if (! is_callable($callback) && ! $callback instanceof static) {
            throw new InvalidArgumentException('The given callback should be callable or an instance of '.static::class);
        }

        static::$defaultCallback = $callback;
    }

    /**
     * Get the default configuration of the dimensions rule.
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
     * Adds the given failures, and return false.
     *
     * @param  array|string  $messages
     * @return bool
     */
    protected function fail($messages)
    {
        $messages = collect(Arr::wrap($messages))->map(
            fn($message) => $this->validator->getTranslator()->get($message)
        )->all();

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
