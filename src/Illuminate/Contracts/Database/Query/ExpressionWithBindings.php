<?php

namespace Illuminate\Contracts\Database\Query;

use Illuminate\Database\Grammar;

interface ExpressionWithBindings
{
    /**
     * Get the value of the expression.
     */
    public function getValue(Grammar $grammar): string;

    /**
     * Get the bindings of the expression.
     */
    public function getBindings(Grammar $grammar): array;
}
