<?php

namespace Illuminate\Tests\Integration\Routing\Fixtures;

enum AbilityBackedEnum: string
{
    case AccessRoute = 'access-route';
    case NotAccessRoute = 'not-access-route';
}
