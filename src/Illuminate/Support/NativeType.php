<?php

declare(strict_types=1);

namespace Illuminate\Support;

enum NativeType: string
{
    case Array = 'array';
    case Bool = 'bool';
    case Callable = 'callable';
    case ClosedResource = 'resource (closed)';
    case Float = 'float';
    case Int = 'int';
    case Iterable = 'iterable';
    case Null = 'null';
    case Numeric = 'numeric';
    case Object = 'object';
    case Resource = 'resource';
    case Scalar = 'scalar';
    case String = 'string';
}
