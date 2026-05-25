<?php

namespace Illuminate\Tests\Auth;

enum AbilitiesEnum: string
{
    case VIEW_DASHBOARD = 'view-dashboard';
    case UPDATE = 'update';
}

enum ModelsEnum: string
{
    case POST = 'post';
    case COMMENT = 'App\Models\Comment';
}
