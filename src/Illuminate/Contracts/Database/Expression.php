<?php namespace Illuminate\Contracts\Database;

interface Expression {

    /**
     * Get the value of the expression.
     *
     * @return mixed
     */
    public function getValue();

    /**
     * Get the value of the expression.
     *
     * @return string
     */
    public function __toString();
}
