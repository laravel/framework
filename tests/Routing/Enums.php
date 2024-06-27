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

enum RouteNameEnum: string
{
    case UserIndex = 'users.index';
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
