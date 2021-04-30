<?php

namespace Illuminate\Database\Eloquent;

use Illuminate\Database\Query\Expression;

class SubSelect extends Expression
{
    /**
     * The parent query builder.
     *
     * @var Builder
     */
    protected $parent;

    /**
     * The sub select query builder.
     *
     * @var Builder
     */
    protected $query;

    /**
     * The select alias.
     *
     * @var string
     */
    protected $as;

    /**
     * Create new SubSelect instance.
     *
     * @param Builder $parent
     * @param Builder $query
     * @param string  $as
     */
    public function __construct(Builder $parent, Builder $query, $as)
    {
        $this->parent = $parent;
        $this->query = $query;
        $this->as = $as;
    }

    /**
     * Get the value of the expression.
     *
     * @return mixed
     */
    public function getValue()
    {
        $bindings = $this->query->getBindings();

        $value = '('.$this->query->toSql().') as '.$this->parent->getGrammar()->wrap($this->as);

        if ($bindings) {
            $this->parent->getQuery()->addBinding($bindings, 'select');
        }

        return $value;
    }
}
