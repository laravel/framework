<?php

namespace Illuminate\Tests\Routing;

enum CategoryEnum
{
    case People;
    case Fruits;
}

enum CategoryBackedEnum: string
{
    case People = 'people';
    case Fruits = 'fruits';
}

enum CategoryIntBackedEnum: int
{
    case People = 1;
    case Fruits = 2;
}
