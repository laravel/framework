<?php

namespace Illuminate\Database\Query;

use Illuminate\Contracts\Database\Query\ExpressionWithBindings as Contract;
use Illuminate\Database\Grammar;

class ExpressionWithBindings implements Contract
{
    /**
     * Create a new database expression.
     */
    public function __construct(
        protected readonly string $sql,
        protected readonly array $bindings = [],
    ) {
    }

    /**
     * Get the value of the expression.
     */
    public function getValue(Grammar $grammar): string
    {
        return $this->sql;
    }

    /**
     * Get the bindings of the expression.
     */
    public function getBindings(Grammar $grammar): array
    {
        return $this->bindings;
    }
}
