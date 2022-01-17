<?php

namespace Illuminate\Support;

use Faker\Generator;

trait UsesFaker
{
    public function __construct(public Generator $faker)
    {
    }
}
