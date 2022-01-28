<?php

namespace Illuminate\Database\Events;

use Illuminate\Database\Eloquent\Model;

class RelationLoadedEvent
{
    /**
     * The model that is loading a relation.
     *
     * @var Model
     */
    private $model;

    /**
     * The name of the relation.
     *
     * @var string
     */
    private $relation;

    /**
     * The results of the relation query.
     *
     * @var mixed
     */
    private $results;

    /**
     * Create a new event instance.
     *
     * @param Model $model
     * @param string $relation
     * @param mixed $results
     */
    public function __construct(Model $model, string $relation, $results)
    {
        $this->model = $model;
        $this->relation = $relation;
        $this->results = $results;
    }
}
