<?php

namespace Illuminate\Tests\Integration\Routing;

enum RouteNameEnum: string {
    case UserIndex = 'users.index';
}

enum RouteDomainEnum: string {
    case DashboardDomain = 'dashboard.myapp.com';
}

enum IntegerEnum: int {
    case One = 1;
    case Two = 2;
}
