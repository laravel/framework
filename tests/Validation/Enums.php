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

enum PureEnum
{
    case one;
    case two;
}
