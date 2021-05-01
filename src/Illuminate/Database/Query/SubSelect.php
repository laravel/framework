<?php

namespace Illuminate\Database\Query;

class SubSelect extends Expression
{
    /**
     * The parent query builder.
     *
     * @var \Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Builder
     */
    protected $parent;

    /**
     * The sub select query builder.
     *
     * @var \Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Builder
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
     * @param \Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Builder $parent
     * @param \Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Builder $query
     * @param string  $as
     */
    public function __construct($parent, $query, $as)
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
            $this->parent->addBinding($bindings, 'select');
        }

        return $value;
    }
}
