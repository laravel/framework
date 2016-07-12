<?php namespace Illuminate\Support\Contracts;

interface RawArrayableInterface {

    /**
    * Get the instance as an array without taking care of visible or hidden.
    *
    * @return array
    */
    public function toRawArray();

}
