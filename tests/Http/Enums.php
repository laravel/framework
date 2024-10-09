<?php

namespace Illuminate\Tests\Http;

enum TestEnumBacked: string
{
    case test = 'test';
    case test_empty = '';
}

enum TestIntegerEnumBacked: int
{
    case minus_1 = -1;
    case zero = 0;
    case plus_1 = 1;
}

enum TestEnum
{
    case test;
}
