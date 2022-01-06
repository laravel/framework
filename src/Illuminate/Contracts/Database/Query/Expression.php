<?php

namespace Illuminate\Contracts\Database\Query;

use Illuminate\Database\Grammar;

interface Expression
{
    /**
     * Get the value of the expression.
     *
     * @param  \Illuminate\Database\Grammar  $grammar
     * @return mixed
     */
    public function getValue(Grammar $grammar);
}
