<?php

namespace Illuminate\Tests\App\Models\Relationships;

class ClassicMechanic extends MockedConnectionModel
{
    public function owner()
    {
        return $this->hasOneThrough(Owner::class, Car::class, 'mechanic_id', 'car_id', 'm_id', 'c_id');
    }
}
