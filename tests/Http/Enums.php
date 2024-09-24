<?php

namespace Illuminate\Tests\Http;

enum TestEnumBacked: string
{
    case test = 'test';
    case test_empty = '';
}

enum TestEnum
{
    case test;
}
