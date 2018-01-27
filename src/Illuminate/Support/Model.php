<?php

namespace Illuminate\Support;

use ArrayAccess;
use Illuminate\Database\Eloquent\MassAssignmentException;
use JsonSerializable;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;

abstract class Model implements ArrayAccess, Arrayable, Jsonable, JsonSerializable
{
    use Concerns\HasAttributes,
        Concerns\HidesAttributes;

    /**
     * Create a new Eloquent model instance.
     *
     * @param  array  $attributes
     * @return void
     */
    public function __construct(array $attributes = [])
    {
        $this->syncOriginal();

        $this->fill($attributes);
    }
}
