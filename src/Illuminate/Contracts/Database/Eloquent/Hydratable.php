<?php


namespace Illuminate\Contracts\Database\Eloquent;


interface Hydratable
{

    /**
     * Get the value of the entity's primary key.
     *
     * @return mixed
     */
    public function getKey();

    /**
     * Get an attribute from the entity.
     *
     * @param  string  $key
     * @return mixed
     */
    public function getAttribute($key);

    /**
     * Get a specified relationship.
     *
     * @param  string  $relation
     * @return mixed
     */
    public function getRelation($relation);

    /**
     * Set the specific relationship in the model.
     *
     * @param  string  $relation
     * @param  mixed  $value
     * @return $this
     */
    public function setRelation($relation, $value);

}