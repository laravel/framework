<?php

namespace Illuminate\Tests\App\Models\Relationships;

class FluentMechanic extends MockedConnectionModel
{
    public function owner()
    {
        return $this->through($this->car())
            ->has(fn (Car $car) => $car->owner());
    }

    public function car()
    {
        return $this->hasOne(Car::class, 'mechanic_id', 'm_id');
    }
}
