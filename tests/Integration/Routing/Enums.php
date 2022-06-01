<?php

namespace Illuminate\Tests\Integration\Routing;

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
