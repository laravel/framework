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

enum AnimalBackedEnum: int
{
    case Platypus = 0;
    case Penguin = 1;
}
