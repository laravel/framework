<?php

namespace Illuminate\Database\Concerns;

use Illuminate\Support\Collection;

trait ExplainsQueries
{
    /**
     * Explains the query.
     *
     * @return \Illuminate\Support\Collection
     */
    public function explain()
    {
        $sql = $this->getGrammar()->compileExplain($this);

        $bindings = $this->getBindings();

        $explanation = $this->getConnection()->select($sql, $bindings);

        return new Collection($explanation);
    }
}
