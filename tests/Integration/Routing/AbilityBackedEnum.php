<?php

namespace Illuminate\Tests\Integration\Routing;

enum AbilityBackedEnum: string
{
    case AccessRoute = 'access-route';
    case NotAccessRoute = 'not-access-route';
}
