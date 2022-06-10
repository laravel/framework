<?php

namespace Illuminate\Tests\Routing;

enum CategoryEnum
{
    case People;
    case Fruits;
    
    case People1;
    case Fruits2;
}

enum CategoryBackedEnum: string
{
    case People = 'people';
    case Fruits = 'fruits';
}
