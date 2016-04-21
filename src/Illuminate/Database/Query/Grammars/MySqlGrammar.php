<?php

namespace Illuminate\Database\Query\Grammars;

use Illuminate\Support\Str;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\JsonExpression;

class MySqlGrammar extends Grammar
{
    /**
     * The components that make up a select clause.
     *
     * @var array
     */
    protected $selectComponents = [
        'aggregate',
        'columns',
        'from',
        'joins',
        'wheres',
        'groups',
        'havings',
        'orders',
        'limit',
        'offset',
        'lock',
    ];

    /**
     * Compile a select query into SQL.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return string
     */
    public function compileSelect(Builder $query)
    {
        $sql = parent::compileSelect($query);

        if ($query->unions) {
            $sql = '('.$sql.') '.$this->compileUnions($query);
        }

        return $sql;
    }

    /**
     * Compile a single union statement.
     *
     * @param  array  $union
     * @return string
     */
    protected function compileUnion(array $union)
    {
        $joiner = $union['all'] ? ' union all ' : ' union ';

        return $joiner.'('.$union['query']->toSql().')';
    }

    /**
     * Compile the lock into SQL.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  bool|string  $value
     * @return string
     */
    protected function compileLock(Builder $query, $value)
    {
        if (is_string($value)) {
            return $value;
        }

        return $value ? 'for update' : 'lock in share mode';
    }

    /**
     * Compile an update statement into SQL.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array  $values
     * @return string
     */
    public function compileUpdate(Builder $query, $values)
    {
        $table = $this->wrapTable($query->from);

        // Each one of the columns in the update statements needs to be wrapped in the
        // keyword identifiers, also a place-holder needs to be created for each of
        // the values in the list of bindings so we can make the sets statements.
        $columns = [];

        foreach ($values as $key => $value) {
            if ($this->isJsonSelector($key)) {
                $columns[] = $this->prepareJsonUpdateColumn($key, new JsonExpression($value));
            } else {
                $columns[] = $this->wrap($key).' = '.$this->parameter($value);
            }
        }

        $columns = implode(', ', $columns);

        // If the query has any "join" clauses, we will setup the joins on the builder
        // and compile them so we can attach them to this update, as update queries
        // can get join statements to attach to other tables when they're needed.
        if (isset($query->joins)) {
            $joins = ' '.$this->compileJoins($query, $query->joins);
        } else {
            $joins = '';
        }

        // Of course, update queries may also be constrained by where clauses so we'll
        // need to compile the where clauses and attach it to the query so only the
        // intended records are updated by the SQL statements we generate to run.
        $where = $this->compileWheres($query);

        $sql = rtrim("update {$table}{$joins} set $columns $where");

        if (isset($query->orders)) {
            $sql .= ' '.$this->compileOrders($query, $query->orders);
        }

        if (isset($query->limit)) {
            $sql .= ' '.$this->compileLimit($query, $query->limit);
        }

        return rtrim($sql);
    }

    /**
     * Prepares the update column for JSON selectors using the JSON_SET MySQL function.
     *
     * @param  string         $key
     * @param  JsonExpression $value
     * @return string
     */
    protected function prepareJsonUpdateColumn($key, JsonExpression $value)
    {
        $path = explode('->', $key);

        $field = $this->wrapValue(array_shift($path));

        $accessor = '"$.'.implode('.', $path).'"';

        $sanitizedValue = $value->getValue();

        return "{$field} = json_set({$field}, {$accessor}, {$sanitizedValue})";
    }

    /**
     * Compile a delete statement into SQL.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return string
     */
    public function compileDelete(Builder $query)
    {
        $table = $this->wrapTable($query->from);

        $where = is_array($query->wheres) ? $this->compileWheres($query) : '';

        if (isset($query->joins)) {
            $joins = ' '.$this->compileJoins($query, $query->joins);

            $sql = trim("delete $table from {$table}{$joins} $where");
        } else {
            $sql = trim("delete from $table $where");
        }

        if (isset($query->orders)) {
            $sql .= ' '.$this->compileOrders($query, $query->orders);
        }

        if (isset($query->limit)) {
            $sql .= ' '.$this->compileLimit($query, $query->limit);
        }

        return $sql;
    }

    /**
     * Check for a JSON selector.
     *
     * @param  string  $value
     * @return bool
     */
    protected function isJsonSelector($value)
    {
        return Str::contains($value, '->');
    }

    /**
     * Compile a basic where clause.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array  $where
     * @return string
     */
    protected function whereBasic(Builder $query, $where)
    {
        // If we have a JSON selector here we'll simply
        // convert it to a JsonExpression which then
        // sets the value correctly on the query
        if ($this->isJsonSelector($where['column']) && is_bool($where['value'])) {
            $this->removeWhereBindingFromQuery($query, $where);

            $where['value'] = new JsonExpression($where['value']);
        }

        $value = $this->parameter($where['value']);

        return $this->wrap($where['column']).' '.$where['operator'].' '.$value;
    }

    /**
     * Removes one where binding from the query.
     *
     * @param  Builder $query
     * @param  array   $where
     * @return void
     */
    protected function removeWhereBindingFromQuery(Builder $query, $where)
    {
        $wheres = $query->wheres;
        $offset = array_search($where, $wheres);

        if ($offset !== false) {
            $whereBindings = $query->getRawBindings()['where'];

            unset($whereBindings[$offset]);

            $query->setBindings($whereBindings, 'where');
        }
    }

    /**
     * Wrap a single string in keyword identifiers.
     *
     * @param  string  $value
     * @return string
     */
    protected function wrapValue($value)
    {
        if ($value === '*') {
            return $value;
        }

        if ($this->isJsonSelector($value)) {
            return $this->wrapJsonSelector($value);
        }

        return '`'.str_replace('`', '``', $value).'`';
    }

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

        return $field.'->'.'"$.'.implode('.', $path).'"';
    }
}
