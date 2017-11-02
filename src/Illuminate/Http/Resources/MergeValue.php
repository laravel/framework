<?php

namespace Illuminate\Http\Resources;

use Illuminate\Collection\Collection;

class MergeValue
{
    /**
     * The data to be merged.
     *
     * @var array
     */
    public $data;

    /**
     * Create new merge value instance.
     *
     * @param  \Illuminate\Collection\Collection|array  $data
     * @return void
     */
    public function __construct($data)
    {
        $this->data = $data instanceof Collection ? $data->all() : $data;
    }
}
