<?php

namespace Illuminate\Tests\Validation;

enum StringStatus: string
{
    case pending = 'pending';
    case done = 'done';
}

enum IntegerStatus: int
{
    case pending = 1;
    case done = 2;
}
