<?php

namespace Illuminate\Tests\Auth;

enum AbilitiesEnum: string
{
    case VIEW_DASHBOARD = 'view-dashboard';
    case UPDATE = 'update';

    case UPDATE_MULTIPLE = 'update-multiple';
}
