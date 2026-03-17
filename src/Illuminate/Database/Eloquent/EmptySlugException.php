<?php

namespace Illuminate\Database\Eloquent;

use Illuminate\Contracts\Debug\ShouldntReport;

class EmptySlugException extends CouldNotGenerateSlugException implements ShouldntReport
{
    //
}
