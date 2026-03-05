<?php

declare(strict_types=1);

namespace Illuminate\Config;

enum ConfigStringType: string
{
    case DEFAULT = 'default';
    case NON_EMPTY = 'nonEmpty';
    case NON_FALSY = 'nonFalsy';
    case LOWERCASE = 'lowercase';
    case UPPERCASE = 'uppercase';
}
