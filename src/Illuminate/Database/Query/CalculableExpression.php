<?php

namespace Illuminate\Database\Query;

class CalculableExpression extends Expression
{
    /**
     * The expected result of the expression.
     *
     * @var mixed
     */
    protected $expectedResult;

    /**
     * Create a new raw query expression.
     *
     * @param  mixed  $value
     * @return void
     */
    public function __construct($value, $expectedResult = null)
    {
        parent::__construct($value);

        $this->expectedResult = $expectedResult;
    }

    /**
     * Get the expected result of the expression.
     *
     * @return mixed
     */
    public function getExpectedResult()
    {
        return $this->expectedResult;
    }

    /**
     * Set the expected result of the expression.
     *
     * @param  mixed  $result
     * @return $this
     */
    public function setExpectedResult($result)
    {
        $this->expectedResult = $result;

        return $this;
    }
}
