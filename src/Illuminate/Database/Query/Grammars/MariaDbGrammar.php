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

    /**
     * Wrap a value in keyword identifiers.
     *
     * @param  \Illuminate\Database\Query\Expression|string  $value
     * @param  bool    $prefixAlias
     * @return string
     */
    public function wrap($value, $prefixAlias = false)
    {
        $mysqlWrap = parent::wrap($value, $prefixAlias);

        // in case there is table name in json path then we have
        // to do some additional replacements
        if(Str::contains($mysqlWrap, '.JSON_EXTRACT')) {

            $path = explode('->', $value);

            $field = collect(explode('.', array_shift($path)))->map(function ($part) {
                return $this->wrapValue($part);
            })->implode('.');

            return sprintf('JSON_EXTRACT(%s, `$.%s`)', $field, collect($path)->map(function ($part) {
                    return '"'.$part.'"';
                })->implode('.')
            );
        }

        return $mysqlWrap;
    }
}