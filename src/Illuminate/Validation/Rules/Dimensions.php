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
     * The width between constraint.
     *
     * @var null|array
     */
    protected $widthBetween = null;

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
     * The height between constraint.
     *
     * @var null|array
     */
    protected $heightBetween = null;

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
     * The aspect ratio range constraint.
     *
     * @var null|array
     */
    protected $ratioBetween = null;

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
     * Additional validation rules that should be merged into the default rules during validation.
     *
     * @var array
     */
    protected $customRules = [];

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
        $this->widthBetween = [$min, $max];

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
        $this->heightBetween = [$min, $max];

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
        $this->ratioBetween = [$min, $max];

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
        $conditions = ['width', 'height', 'ratio'];

        foreach (get_object_vars($this) as $property => $value) {
            if ($value !== null && Str::contains($property, $conditions, true) ) {
                if (is_array($value)) {
                    $value = implode(',', $value);
                }
                $rule = Str::snake($property);
                $rules[] = "{$rule}:{$value}";
            }
        }

        return array_merge(array_filter($rules), $this->customRules);
    }

    /**
     * Set the default callback to be used for determining default rules.
     *
     * If no arguments are passed, the default dimensions rule configuration will be returned.
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
     * Get the default configuration of the dimensions rule.
     *
     * @return static
     */
    public static function default()
    {
        $dimensions = is_callable(static::$defaultCallback)
            ? call_user_func(static::$defaultCallback)
            : static::$defaultCallback;

        return $dimensions instanceof Rule ? $dimensions : new self();
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
