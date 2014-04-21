<?php namespace Illuminate\Database\Eloquent;

interface ObservableInterface {

    /**
     * Get the observable event names.
     *
     * @return array
     */
    public function getObservableEvents();
}
