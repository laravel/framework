<?php

namespace Illuminate\Tests\Database\Fixtures\Enums;

enum Roles: string
{
    case Member = 'member';
    case Admin = 'admin';
}
