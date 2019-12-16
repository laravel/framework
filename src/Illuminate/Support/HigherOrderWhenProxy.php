<?php

namespace Illuminate\Support;

use Illuminate\Http\Resources\MissingValue;

class HigherOrderWhenProxy
{
    /**
     * The target being proxied.
     *
     * @var mixed
     */
    public $target;

    /**
     * The target is missing.
     *
     * @var bool
     */
    private $isMissing;

    /**
     * Create a new proxy instance.
     *
     * @param  mixed  $target
     * @param  bool $isMissing
     * @return void
     */
    public function __construct($target, bool $isMissing)
    {
        $this->target = $target;
        $this->isMissing = $isMissing;
    }

    /**
     * Dynamically pass method calls to the target.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->isMissing
            ? new MissingValue()
            : $this->target->{$method}(...$parameters);
    }

    /**
     * Dynamically pass attribute calls to the target.
     *
     * @param  string  $attributeName
     * @return mixed
     */
    public function __get($attributeName)
    {
        return $this->isMissing
            ? new MissingValue()
            : $this->target->{$attributeName};
    }
}
