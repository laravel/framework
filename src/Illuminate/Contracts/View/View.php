<?php

namespace Illuminate\Contracts\View;

use Illuminate\Contracts\Support\Renderable;

interface View extends Renderable
{
    /**
     * Get the name of the view.
     *
     * @return string
     */
    public function name();

    /**
     * Add a piece of data to the view.
     *
     * @param  string|array  $key
     * @param  mixed  $value
     * @return $this
     */
    public function with($key, $value = null);

    /**
     * Get the array of view data.
     *
     * @return array
     */
    public function getData();


    /**
     * Get the evaluated contents of a given fragment.
     *
     * @param  string  $fragment
     * @return string
     */
    public function fragment($fragment);


    /**
     * Get the evaluated contents for a given array of fragments or return all fragments.
     *
     * @param  array|null  $fragments
     * @return string
     */
    public function fragments(?array $fragments = null);


    /**
     * Get the evaluated contents of a given fragment if the given condition is true.
     *
     * @param  bool  $boolean
     * @param  string  $fragment
     * @return string
     */
    public function fragmentIf($boolean, $fragment);


    /**
     * Get the evaluated contents for a given array of fragments if the given condition is true.
     *
     * @param  bool  $boolean
     * @param  array|null  $fragments
     * @return string
     */
    public function fragmentsIf($boolean, ?array $fragments = null);
}
