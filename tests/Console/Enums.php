<?php

namespace Illuminate\Tests\Console;

enum Enum
{
    case A;
    case B;
    case C;
}

enum IntEnum: int
{
    case A = 1;
    case B = 2;
    case C = 3;
}

enum StringEnum: string
{
    case A = 'String A';
    case B = 'String B';
    case C = 'String C';
}
