<?php

namespace Illuminate\Contracts\Container;

interface Factory
{
    /**
     * Build a bind for container.
     *
     * @return mixed
     */
    public function build();
}
