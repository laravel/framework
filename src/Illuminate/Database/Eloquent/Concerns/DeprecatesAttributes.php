<?php

namespace Illuminate\Database\Eloquent\Concerns;

trait DeprecatesAttributes
{
    /**
     * The attributes that are deprecated.
     *
     * @var array<string>
     */
    protected $deprecated = [];

    /**
     * Get the deprecated attributes for the model.
     *
     * @return array<string>
     */
    public function getDeprecated()
    {
        return $this->deprecated;
    }
}
