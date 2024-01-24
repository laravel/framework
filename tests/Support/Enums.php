<?php

namespace Illuminate\Tests\Support;

enum TestEnum
{
    case A;
}

enum TestBackedEnum: int
{
    case A = 1;
    case B = 2;
}
