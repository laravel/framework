<?php

namespace Illuminate\Database\Concerns;

trait QuotesValue
{
    /**
     * Quote the given string literal.
     *
     * @param  string  $value
     * @return string
     */
    protected function quoteValue($value)
    {
        return $this->connection->quoteString($value);
    }
}
