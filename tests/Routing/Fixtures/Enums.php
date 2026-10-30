<?php

namespace Illuminate\Tests\Routing\Fixtures;

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

enum RouteDomainEnum: string
{
    case DashboardDomain = 'dashboard.myapp.com';
}

enum IntegerEnum: int
{
    case One = 1;
    case Two = 2;
}
