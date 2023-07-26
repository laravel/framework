<?php

namespace Illuminate\Database\Eloquent\Factories;

trait HasRealTimeFactory
{
    use HasFactory;

    /**
     * Create a new factory instance for the model.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory<static>
     */
    protected static function newFactory()
    {
        return (new RealTimeFactory)
            ->forModel(get_called_class())
            ->configure();
    }
}
