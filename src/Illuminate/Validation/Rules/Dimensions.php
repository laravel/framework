<?php

namespace Illuminate\Validation\Rules;

use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Contracts\Validation\ValidatorAwareRule;
use Illuminate\Support\Traits\Conditionable;
use Illuminate\Support\Traits\Macroable;

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
     * @return string
     */
    public function __toString()
    {
        $result = '';

        foreach ($this->constraints as $key => $value) {
            $result .= "$key=$value,";
        }

        return 'dimensions:'.substr($result, 0, -1);
    }
}
