<?php namespace Illuminate\Support\Contracts;

interface RawJsonableInterface {

    /**
     * Convert the object to its JSON representation without taking care of visible or hidden.
     *
     * @param  int  $options
     * @return string
     */
    public function toRawJson($options = 0);

}
