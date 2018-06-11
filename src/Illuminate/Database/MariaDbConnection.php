<?php

namespace Illuminate\Database;

use Illuminate\Database\Query\Grammars\MariaDbGrammar as QueryGrammar;

class MariaDbConnection extends MySqlConnection
{
    protected function getDefaultQueryGrammar()
    {
        return new QueryGrammar;
    }
}
