<?php

namespace Illuminate\Database\Query;

use Illuminate\Contracts\Database\Query\Expression as ExpressionContract;
use Illuminate\Database\Grammar;

class Expression implements ExpressionContract
{
    /**
     * The value of the expression.
     *
     * @var mixed
     */
    protected $value;

    /**
     * Create a new raw query expression.
     *
     * @param  mixed  $value
     * @return void
     */
    public function __construct($value)
    {
        $this->value = $value;
    }

    /**
     * Get the value of the expression.
     *
     * @param  \Illuminate\Database\Grammar  $grammar
     * @return mixed
     */
    public function getValue(Grammar $grammar)
    {
        return $this->value;
    }
}
