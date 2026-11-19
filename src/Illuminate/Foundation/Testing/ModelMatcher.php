<?php

namespace Illuminate\Foundation\Testing;

use Illuminate\Database\Eloquent\Model;
use Mockery\Matcher\MatcherInterface;

class ModelMatcher implements MatcherInterface
{
    /**
     * Create a new model matcher instance.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $expected
     */
    public function __construct(protected Model $expected)
    {
    }

    /**
     * Determine if the given value is the expected model.
     *
     * @param  mixed  $actual
     * @return bool
     */
    public function match(&$actual)
    {
        return $actual instanceof Model && $this->expected->is($actual);
    }

    /**
     * Get the string representation of the matcher.
     *
     * @return string
     */
    public function __toString()
    {
        return '<Model '.get_class($this->expected).' #'.$this->expected->getKey().'>';
    }
}
