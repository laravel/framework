<?php

namespace Illuminate\Database\Eloquent\Relations;

use Illuminate\Database\Eloquent\Collection;

class MorphManyTo extends MorphOneOrManyTo
{
    /**
     * Match the results for a given type to their parents.
     *
     * @param  string  $type
     * @param  \Illuminate\Database\Eloquent\Collection  $results
     * @return void
     */
    protected function matchToMorphParents($type, Collection $results)
    {
        if ($results->isNotEmpty()) {
            $result = $results->first();
            $ownerKey = $this->ownerKey ?? $result->getKeyName();
            $results = $results->groupBy($ownerKey);
        }
        $keys = array_keys($this->dictionary[$type]);
        foreach ($keys as $key) {
            $result = $results[$key] ?? $this->related->newCollection();
            if (isset($this->dictionary[$type][$key])) {
                foreach ($this->dictionary[$type][$key] as $model) {
                    $model->setRelation($this->relationName, $result);
                }
            }
        }
    }

    /**
     * Get the results of the relationship.
     *
     * @return mixed
     */
    public function getResults()
    {
        if (is_null($this->child->{$this->foreignKey})) {
            return $this->getDefaultFor($this->parent);
        }

        return $this->query->get() ?: $this->getDefaultFor($this->parent);
    }
}
