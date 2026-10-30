<?php

namespace Illuminate\Database\Eloquent\Concerns;

trait HasDefaultAttributes
{
    /**
     * Get the default attribute values for the model.
     *
     * @return array<string, mixed>
     */
    protected function defaults()
    {
        return [];
    }
}
