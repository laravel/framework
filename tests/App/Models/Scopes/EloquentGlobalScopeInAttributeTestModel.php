<?php

namespace Illuminate\Tests\App\Models\Scopes;

use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Tests\App\Scopes\ActiveScope;

#[ScopedBy(ActiveScope::class)]
class EloquentGlobalScopeInAttributeTestModel extends Model
{
    protected $table = 'table';
}
