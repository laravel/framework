<?php

namespace Illuminate\Tests\App\Models\Scopes;

use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Tests\App\Scopes\ActiveScope;

#[ScopedBy(ActiveScope::class)]
trait EloquentGlobalScopeInInheritedAttributeTestTrait
{
    //
}
