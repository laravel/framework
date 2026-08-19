<?php

namespace Illuminate\Tests\App\Casts;

class DateTimezoneCasterWithoutObjectCaching extends DateTimezoneCasterWithObjectCaching
{
    public bool $withoutObjectCaching = true;
}
