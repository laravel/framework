<?php

namespace Illuminate\Database\Query\Grammars;

use Illuminate\Database\Query\Builder;

class MariaDbGrammar extends MySqlGrammar
{
    /**
     * Determine whether to use a legacy group limit clause for MySQL < 8.0.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return bool
     */
    public function useLegacyGroupLimit(Builder $query)
    {
        return false;
    }
}
