<?php

namespace Illuminate\Database\Eloquent\Relations\Concerns;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

trait SupportsReverseRelations
{
    /**
     * Array of reverse relations names in related model.
     *
     * @var array
     */
    protected $withReverses = [];

    /**
     * Set reverse relation in related model to parent model.
     *
     * @param  null|string|array  $reverses
     * @return $this
     */
    public function withReverse($reverses = null)
    {
        $reverses = is_null($reverses) ? [Str::camel(class_basename($this->parent))] : $reverses;

        $reverses = is_array($reverses) ? $reverses : func_get_args();

        $this->withReverses = array_merge($this->withReverses, $reverses);

        $this->query->without($reverses);

        return $this;
    }

    /**
     * Determine if the related model has any reverse relation.
     *
     * @return boolean
     */
    public function hasReverse()
    {
        return count($this->withReverses) > 0;
    }

    /**
     * Get array of reverse relationships.
     *
     * @return array
     */
    public function getReverses()
    {
        return $this->withReverses;
    }

    /**
     * Set reverse relationship if reverse relation exists and results are valid.
     *
     * @param  mixed  $results
     * @param  null|Model  $parent
     * @return mixed
     */
    protected function setReverseRelation($results, $parent = null)
    {
        if ($this->hasReverse()) {
            $parent = $parent ?? $this->parent;

            if ($results instanceof Model) {
                foreach ($this->withReverses as $reverseRelation) {
                    $results->setRelation($reverseRelation, $parent);
                }
            } elseif ($results instanceof Collection || $results instanceof LengthAwarePaginator) {
                $items = $results instanceof LengthAwarePaginator ? $results->items() : $results;
                foreach ($items as $item) {
                    if ($item instanceof Model) {
                        foreach ($this->withReverses as $reverseRelation) {
                            $item->setRelation($reverseRelation, $parent);
                        }
                    }
                }
            }
        }

        return $results;
    }

    /**
     * Execute the query as a "select" statement.
     *
     * @param  array  $columns
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function get($columns = ['*'])
    {
        return $this->setReverseRelation(
            parent::get($columns)
        );
    }

    /**
     * Handle dynamic method calls to the relationship.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->setReverseRelation(
            parent::__call($method, $parameters)
        );
    }
}
