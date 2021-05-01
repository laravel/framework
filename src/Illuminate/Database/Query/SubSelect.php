<?php

namespace Illuminate\Database\Query;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;

class SubSelect extends Expression
{
    /**
     * The parent query builder.
     *
     * @var \Illuminate\Database\Query\Builder
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
     * Determines whether the bindings have ben added to the parent query.
     *
     * @var boolean
     */
    protected $binded = false;

    /**
     * Create new SubSelect instance.
     *
     * @param \Illuminate\Database\Query\Builder $parent
     * @param \Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Builder $query
     * @param string  $as
     */
    public function __construct(Builder $parent, $query, $as)
    {
        $this->parent = $parent;
        $this->query = $query;
        $this->as = $as;
    }

    /**
     * Set parent query builder.
     *
     * @param \Illuminate\Database\Query\Builder $parent
     * @return $this
     */
    public function setParent(Builder $parent)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * Get parent query builder object.
     *
     * @return Builder
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Get the value of the expression.
     *
     * @return mixed
     */
    public function getValue()
    {
        $this->addBindings();

        return '('.$this->query->toSql().') as '.$this->parent->getGrammar()->wrap($this->as);
    }

    /**
     * Add the subselect bindings to the parent query builder.
     *
     * @return void
     */
    protected function addBindings()
    {
        if ($this->binded) {
            return false;
        }

        if (!$bindings = $this->query->getBindings()) {
            return;
        }

        $this->parent->addBinding($bindings, 'select');

        $this->binded = true;
    }
}
