<?php

namespace Illuminate\Http\Request\Enums;

enum HttpQueryEncoding: int
{
    case Rfc1738 = PHP_QUERY_RFC1738;
    case Rfc3986 = PHP_QUERY_RFC3986;
}
