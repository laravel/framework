<?php

namespace Illuminate\Database\Query\Grammars;

use Illuminate\Support\Str;

class MariaDbGrammar extends MySqlGrammar 
{

    /**
     * Wrap the given JSON selector.
     *
     * @param  string  $value
     * @return string
     */
    protected function wrapJsonSelector($value)
    {
        $path = explode('->', $value);

        $field = $this->wrapValue(array_shift($path));

        return sprintf('JSON_EXTRACT(%s, `$.%s`)', $field, collect($path)->map(function ($part) {
            return '"'.$part.'"';
        })->implode('.'));
    }
}