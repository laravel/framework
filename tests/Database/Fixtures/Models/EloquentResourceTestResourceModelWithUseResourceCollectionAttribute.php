<?php

namespace Illuminate\Tests\Database\Fixtures\Models;

use Illuminate\Database\Eloquent\Attributes\UseResourceCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Tests\Database\Fixtures\Resources\EloquentResourceTestJsonResourceCollection;

#[UseResourceCollection(EloquentResourceTestJsonResourceCollection::class)]
class EloquentResourceTestResourceModelWithUseResourceCollectionAttribute extends Model
{
    //
}
