<?php

namespace Illuminate\Database\Query;

use Illuminate\Contracts\Database\Query\Builder as BuilderContract;

/**
 * Class CteClause
 * Handles a Common Table Expression.
 */
class CteClause extends Builder
{
    /**
     * @param  Builder  $parentQuery
     * @param  string  $aliasName
     * @param  array  $aliasColumns
     * @param  bool  $recursive
     */
    public function __construct(
        protected BuilderContract $parentQuery,
        public string $aliasName,
        public array $aliasColumns = [],
        public bool $recursive = false
    ) {
        parent::__construct(
            $this->parentQuery->getConnection(),
            $this->parentQuery->getGrammar(),
            $this->parentQuery->getProcessor()
        );
    }

    /**
     * Get a new instance of the join clause builder.
     *
     * @return static
     */
    public function newQuery()
    {
        return new static($this->newParentQuery(), $this->recursive);
    }

    /**
     * Create a new query instance for sub-query.
     *
     * @return Builder
     */
    protected function forSubQuery()
    {
        return $this->newParentQuery()->newQuery();
    }

    /**
     * Create a new parent query instance.
     *
     * @return Builder
     */
    protected function newParentQuery()
    {
        $parentQueryClass = get_class($this->parentQuery);

        return new $parentQueryClass(
            $this->parentQuery->getConnection(),
            $this->parentQuery->getGrammar(),
            $this->parentQuery->getProcessor()
        );
    }
}
