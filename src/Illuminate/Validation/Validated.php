<?php

namespace Illuminate\Validation;

use Illuminate\Database\Eloquent\Fillable;

class Validated implements Fillable
{
    /**
     * @var array
     */
    private $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function all()
    {
        return $this->data;
    }
}
