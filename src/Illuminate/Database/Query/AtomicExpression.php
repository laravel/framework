<?php

namespace Illuminate\Database\Query;

class AtomicExpression extends Expression
{
    /**
     * The expected outcome of the expression.
     *
     * Expressions with a calculation on a column are expected to take a certain outcome, depending on what value the
     * application currently gives to the column.
     */
    protected $expectedOutcome = null;

    /**
     * Create a new raw query expression.
     *
     * @param  mixed  $value
     * @return void
     */
    public function __construct($value, $expectedOutcome)
    {
        $this->value = $value;
        $this->expectedOutcome = $expectedOutcome;
    }

    /**
     * Get the expected outcome of the expression.
     *
     * @return mixed
     */
    public function getExpectedOutcome()
    {
        return $this->expectedOutcome;
    }
}
