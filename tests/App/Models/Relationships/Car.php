<?php

namespace Illuminate\Tests\App\Models\Relationships;

class Car extends MockedConnectionModel
{
    public function owner()
    {
        return $this->hasOne(Owner::class, 'car_id', 'c_id');
    }
}
