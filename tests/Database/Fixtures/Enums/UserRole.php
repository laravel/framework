<?php

namespace Illuminate\Tests\Database\Fixtures\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Member = 'member';
    case Guest = 'guest';
}
