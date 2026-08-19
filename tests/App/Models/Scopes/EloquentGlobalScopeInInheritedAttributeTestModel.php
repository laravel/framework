<?php

namespace Illuminate\Tests\App\Models\Scopes;

use Illuminate\Database\Eloquent\Model;

class EloquentGlobalScopeInInheritedAttributeTestModel extends Model
{
    use EloquentGlobalScopeInInheritedAttributeTestTrait;

    protected $table = 'table';
}
